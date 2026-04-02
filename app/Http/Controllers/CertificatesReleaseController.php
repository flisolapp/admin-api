<?php

namespace App\Http\Controllers;

use App\Helpers\CertificateHelper;
use App\Helpers\StringHelper;
use App\Models\Collaborator;
use App\Models\Edition;
use App\Models\Organizer;
use App\Models\Participant;
use App\Models\PeopleCertificate;
use App\Models\Talk;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CertificatesReleaseController extends Controller
{
    /**
     * Release certificates for the current active edition.
     *
     * This endpoint creates missing certificate records for all eligible people
     * in the active edition, covering the following categories:
     * - Organizers
     * - Collaborators
     * - Speakers linked to talks
     * - Participants
     *
     * After the certificate records are created, any certificate without a public
     * verification code receives a unique code and has its name normalized before saving.
     *
     * The creation of missing certificate records is executed inside a database
     * transaction. If any error occurs during this phase, the operation is rolled back.
     *
     * Possible responses:
     * - 200: Certificates released successfully
     * - 404: No active edition was found
     * - 500: Failed to populate certificate records
     *
     * @return JsonResponse
     */
    public function execute(): JsonResponse
    {
        set_time_limit(0);

        $edition = Edition::where('active', 1)->orderByDesc('id')->first();

        if (!$edition) {
            return response()->json([
                'error' => 'No active edition found.',
            ], 404);
        }

        $now = Carbon::now();

        DB::beginTransaction();

        try {
            // Create organizer certificates when missing
            Organizer::where('edition_id', $edition->id)
                ->whereNull('removed_at')
                ->with('person')
                ->get()
                ->each(function ($organizer) use ($edition, $now) {
                    if (!PeopleCertificate::where([
                        ['people_id', $organizer->people_id],
                        ['edition_id', $edition->id],
                        ['organizer_id', $organizer->id],
                        ['collaborator_id', null],
                        ['talk_id', null],
                        ['participant_id', null],
                    ])->exists()) {
                        PeopleCertificate::create([
                            'people_id' => $organizer->people_id,
                            'edition_id' => $edition->id,
                            'organizer_id' => $organizer->id,
                            'name' => $organizer->person->name,
                            'federal_code' => $organizer->person->federal_code,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });

            // Create collaborator certificates when missing
            Collaborator::where('edition_id', $edition->id)
                ->whereNull('removed_at')
                ->with('person')
                ->get()
                ->each(function ($collaborator) use ($edition, $now) {
                    if (!PeopleCertificate::where([
                        ['people_id', $collaborator->people_id],
                        ['edition_id', $edition->id],
                        ['organizer_id', null],
                        ['collaborator_id', $collaborator->id],
                        ['talk_id', null],
                        ['participant_id', null],
                    ])->exists()) {
                        PeopleCertificate::create([
                            'people_id' => $collaborator->people_id,
                            'edition_id' => $edition->id,
                            'collaborator_id' => $collaborator->id,
                            'name' => $collaborator->person->name,
                            'federal_code' => $collaborator->person->federal_code,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });

            // Create speaker certificates for each talk speaker when missing
            Talk::where('edition_id', $edition->id)
                ->whereNull('removed_at')
                ->with(['speakerTalks.person'])
                ->get()
                ->each(function ($talk) use ($edition, $now) {
                    foreach ($talk->speakerTalks as $speakerTalk) {
                        if (!PeopleCertificate::where([
                            ['people_id', $speakerTalk->speaker_id],
                            ['edition_id', $edition->id],
                            ['organizer_id', null],
                            ['collaborator_id', null],
                            ['talk_id', $talk->id],
                            ['participant_id', null],
                        ])->exists()) {
                            PeopleCertificate::create([
                                'people_id' => $speakerTalk->speaker_id,
                                'edition_id' => $edition->id,
                                'talk_id' => $talk->id,
                                'name' => $speakerTalk->person->name,
                                'federal_code' => $speakerTalk->person->federal_code,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                });

            // Create participant certificates when missing
            Participant::where('edition_id', $edition->id)
                ->whereNull('removed_at')
                ->with('person')
                ->get()
                ->each(function ($participant) use ($edition, $now) {
                    if (!PeopleCertificate::where([
                        ['people_id', $participant->people_id],
                        ['edition_id', $edition->id],
                        ['organizer_id', null],
                        ['collaborator_id', null],
                        ['talk_id', null],
                        ['participant_id', $participant->id],
                    ])->exists()) {
                        PeopleCertificate::create([
                            'people_id' => $participant->people_id,
                            'edition_id' => $edition->id,
                            'participant_id' => $participant->id,
                            'name' => $participant->person->name,
                            'federal_code' => $participant->person->federal_code,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });

            DB::commit();

            // Assign public verification codes and normalize names
            $certificates = PeopleCertificate::where('edition_id', $edition->id)
                ->whereNull('removed_at')
                ->whereNull('code')
                ->get();

            foreach ($certificates as $certificate) {
                $certificate->name = StringHelper::prepareName($certificate->name);
                $certificate->code = $this->generateUniqueCode();
                $certificate->updated_at = $now;
                $certificate->save();
            }

            return response()->json([
                'message' => 'Certificates released successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error populating certificates: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to populate certificates.',
            ], 500);
        }
    }

    /**
     * Generate a unique public certificate verification code.
     *
     * The code is generated repeatedly until a value is found that does not
     * already exist in the people_certificates table.
     *
     * @return string
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = CertificateHelper::generateCode();
        } while (PeopleCertificate::where('code', $code)->exists());

        return $code;
    }
}
