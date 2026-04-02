<?php

namespace App\Http\Controllers;

use App\Helpers\CertificateHelper;
use App\Helpers\ColorHelper;
use App\Helpers\StorageCacheHelper;
use App\Helpers\StringHelper;
use App\Models\PeopleCertificate;
use DateTime;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificatesDownloadController extends Controller
{
    /**
     * Generate and download a certificate image as PNG.
     *
     * This endpoint validates the provided certificate code, resolves the correct
     * certificate template for the certificate type, renders the participant name
     * and optional metadata on top of the template, stores the generated PNG in cache,
     * updates the certificate last view timestamp, and streams the final file.
     *
     * When a previously generated PNG already exists in cache, the cached file is
     * streamed directly instead of rendering the image again.
     *
     * Possible responses:
     * - 200: Certificate PNG file streamed successfully
     * - 404: Certificate, edition, font, or template was not found
     * - 500: Certificate image generation failed
     *
     * @param string $code Unique public certificate verification code.
     *
     * @return StreamedResponse|JsonResponse
     */
    public function execute(string $code): StreamedResponse|JsonResponse
    {
        // Remove PHP time and memory limits for large image processing
        $this->removeMemoryAndTimeLimits();

        // Retrieve certificate with required relationships
        $certificate = PeopleCertificate::with(['edition', 'talk'])
            ->where('code', $code)
            ->whereNull('removed_at')
            ->first();

        // Certificate or related edition not found
        if (!$certificate || !$certificate->edition) {
            return response()->json(['found' => false], 404);
        }

        $edition = $certificate->edition;
        $certificateOptions = $edition->options->certificate ?? null;
        $editionId = $edition->id;
        $name = $certificate->name;
        $codeVerificationUrl = 'https://certified.flisol.app/' . $certificate->code;

        // Serve cached PNG when available
        $cachedCertificate = StorageCacheHelper::get("certificates/{$editionId}/{$certificate->code}.png");

        if ($cachedCertificate && file_exists($cachedCertificate)) {
            return Response::streamDownload(function () use ($cachedCertificate) {
                readfile($cachedCertificate);
            }, 'certificate_' . $certificate->code . '.png', [
                'Content-Type' => 'image/png',
            ]);
        }

        // Load and locally cache the font file configured for the edition
        $fontFileName = $certificateOptions->font ?? 'NunitoSans-Bold.ttf';
        $fontKey = "editions/{$editionId}/{$fontFileName}";
        $font = StorageCacheHelper::get($fontKey);

        if (!$font || !file_exists($font)) {
            return response()->json([
                'found' => false,
                'error' => 'Font not found',
            ], 404);
        }

        // Resolve certificate background template and main text color
        [$certificateFile, $colorHex] = $this->resolveCertificateTemplate(
            $certificate,
            $certificateOptions,
            $editionId
        );

        if (!$certificateFile || !file_exists($certificateFile)) {
            return response()->json([
                'found' => false,
                'error' => 'Template not found',
            ], 404);
        }

        // Load the certificate template image
        $image = @imagecreatefrompng($certificateFile);

        // Render participant name
        if ($name) {
            [$firstLine, $secondLine] = StringHelper::splitTextBySpace($name, 23);

            // Shadow for readability
            $shadow = imagecolorallocate($image, 240, 240, 240);
            imagefttext($image, 60, 0, 109, 471, $shadow, $font, $firstLine);

            if ($secondLine) {
                imagefttext($image, 60, 0, 109, 551, $shadow, $font, $secondLine);
            }

            // Main name text color
            $rgb = ColorHelper::hexToRgb($colorHex);
            $nameColor = imagecolorallocate($image, $rgb->red, $rgb->green, $rgb->blue);
            imagefttext($image, 60, 0, 108, 470, $nameColor, $font, $firstLine);

            if ($secondLine) {
                imagefttext($image, 60, 0, 108, 550, $nameColor, $font, $secondLine);
            }
        }

        // Render talk title for speaker-related certificates
        if ($certificate->talk) {
            $title = $certificate->talk->title;
            $titleColor = imagecolorallocate($image, 74, 79, 82);
            imagefttext($image, 18, 0, 114, 620, $titleColor, $font, $title);
        }

        // Render CPF when available
        if ($certificate->federal_code) {
            CertificateHelper::addFederalCode($image, 'CPF', $certificate->federal_code, 12, 23);
        }

        // Render verification URL and QR code
        CertificateHelper::addCodeVerificationUrl($image, $codeVerificationUrl, 1586, 0);
        CertificateHelper::addQrCode($image, $codeVerificationUrl, 1280, 780);

        // Convert image resource to PNG binary
        $data = CertificateHelper::getData($image);

        if ($data === null) {
            return response()->json([
                'found' => false,
                'error' => 'Image generation failed',
            ], 500);
        }

        // Update visualization timestamp
        $certificate->last_view_at = DateTimeImmutable::createFromMutable(new DateTime());
        $certificate->save();

        // Persist generated file into cache
        StorageCacheHelper::save("certificates/{$editionId}/{$certificate->code}.png", $data);

        // Stream generated PNG as download
        return Response::streamDownload(function () use ($data) {
            echo $data;
        }, 'certificate_' . $certificate->code . '.png', [
            'Content-Type' => 'image/png',
        ]);
    }

    /**
     * Remove PHP execution time and memory limits for certificate rendering.
     *
     * This is required because certificate generation may involve large PNG files,
     * custom fonts, text rendering, QR code generation, and image manipulation.
     *
     * @return void
     */
    private function removeMemoryAndTimeLimits(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
    }

    /**
     * Resolve the certificate template file and main text color for a certificate.
     *
     * The template is selected according to the certificate role priority:
     * Organizer > Collaborator > Talk/Speaker > Participant
     *
     * Each role must exist on the certificate record and also be configured in the
     * edition certificate options. The template file is retrieved from storage and
     * cached locally before being returned.
     *
     * When no matching template is found, the method returns `null` as file path and
     * the default certificate text color.
     *
     * @param PeopleCertificate $certificate Certificate being rendered.
     * @param object|null $certificateOptions Edition certificate configuration object.
     * @param int|string $editionId Edition identifier used to resolve storage paths.
     *
     * @return array{0: string|null, 1: string}
     */
    private function resolveCertificateTemplate(
        PeopleCertificate $certificate,
        ?object $certificateOptions,
        int|string $editionId
    ): array {
        $defaultColor = '#FE8200';

        $types = [
            'organizer' => 'organizer',
            'collaborator' => 'collaborator',
            'talk' => 'speaker',
            'participant' => 'participant',
        ];

        foreach ($types as $property => $optionKey) {
            if ($certificate->$property && isset($certificateOptions->$optionKey)) {
                $option = $certificateOptions->$optionKey;

                // Load template file from storage and cache locally
                $fileKey = "editions/{$editionId}/{$option->file}";
                $file = StorageCacheHelper::get($fileKey);

                return [$file, $option->color];
            }
        }

        return [null, $defaultColor];
    }
}
