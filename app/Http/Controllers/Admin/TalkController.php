<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Talk;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TalkController extends Controller
{
    /** GET /api/talks */
    public function index(Request $request): JsonResponse
    {
        $talks = Talk::with(['speakers.person', 'talkSubject'])
            ->when($request->edition_id, fn($q, $id) => $q->where('edition_id', $id))
            ->when($request->kind,       fn($q, $k)  => $q->where('kind', $k))
            ->when($request->confirmed !== null, fn($q) =>
                $q->where('confirmed', filter_var($request->confirmed, FILTER_VALIDATE_BOOLEAN))
            )
            ->orderByDesc('id')
            ->get()
            ->map(fn($t) => $this->format($t));

        return response()->json($talks);
    }

    /** GET /api/talks/{id} */
    public function show(Talk $talk): JsonResponse
    {
        $talk->load(['speakers.person', 'talkSubject']);
        return response()->json($this->format($talk));
    }

    /** POST /api/talks */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:200'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'kind'             => ['required', 'in:P,M,O'],
            'edition_id'       => ['required', 'exists:editions,id'],
            'talk_subject_id'  => ['nullable', 'exists:talk_subjects,id'],
            'room'             => ['nullable', 'string', 'max:80'],
            'scheduled_at'     => ['nullable', 'string', 'max:10'],
            'speaker_ids'      => ['nullable', 'array'],
            'speaker_ids.*'    => ['exists:speakers,id'],
            'slide'            => ['nullable', 'file', 'max:20480'],
        ]);

        $talk = DB::transaction(function () use ($data, $request) {
            $talk = Talk::create([
                'title'           => $data['title'],
                'description'     => $data['description'] ?? null,
                'kind'            => $data['kind'],
                'edition_id'      => $data['edition_id'],
                'talk_subject_id' => $data['talk_subject_id'] ?? null,
                'room'            => $data['room'] ?? null,
                'scheduled_at'    => $data['scheduled_at'] ?? null,
                'confirmed'       => false,
            ]);

            if (! empty($data['speaker_ids'])) {
                $talk->speakers()->sync($data['speaker_ids']);
            }

            if ($request->hasFile('slide')) {
                $path = $request->file('slide')->store(
                    "talks/{$talk->id}/slide", 's3'
                );
                $talk->update(['slide_path' => $path]);
            }

            return $talk;
        });

        return response()->json($this->format($talk->load(['speakers.person', 'talkSubject'])), 201);
    }

    /** PUT/PATCH /api/talks/{id} */
    public function update(Request $request, Talk $talk): JsonResponse
    {
        $data = $request->validate([
            'title'           => ['sometimes', 'string', 'max:200'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'kind'            => ['sometimes', 'in:P,M,O'],
            'talk_subject_id' => ['nullable', 'exists:talk_subjects,id'],
            'room'            => ['nullable', 'string', 'max:80'],
            'scheduled_at'    => ['nullable', 'string', 'max:10'],
            'confirmed'       => ['sometimes', 'boolean'],
            'speaker_ids'     => ['nullable', 'array'],
            'speaker_ids.*'   => ['exists:speakers,id'],
            'slide'           => ['nullable', 'file', 'max:20480'],
        ]);

        DB::transaction(function () use ($data, $request, $talk) {
            $talk->update(array_filter([
                'title'           => $data['title']           ?? null,
                'description'     => $data['description']     ?? null,
                'kind'            => $data['kind']            ?? null,
                'talk_subject_id' => $data['talk_subject_id'] ?? null,
                'room'            => $data['room']            ?? null,
                'scheduled_at'    => $data['scheduled_at']   ?? null,
                'confirmed'       => $data['confirmed']       ?? null,
            ], fn($v) => $v !== null));

            if (isset($data['speaker_ids'])) {
                $talk->speakers()->sync($data['speaker_ids']);
            }

            if ($request->hasFile('slide')) {
                if ($talk->slide_path) {
                    Storage::disk('s3')->delete($talk->slide_path);
                }
                $path = $request->file('slide')->store(
                    "talks/{$talk->id}/slide", 's3'
                );
                $talk->update(['slide_path' => $path]);
            }
        });

        return response()->json($this->format($talk->load(['speakers.person', 'talkSubject'])));
    }

    /** PATCH /api/talks/{id}/confirm */
    public function confirm(Request $request, Talk $talk): JsonResponse
    {
        $data = $request->validate(['confirmed' => ['required', 'boolean']]);
        $talk->update($data);

        return response()->json($this->format($talk->load(['speakers.person', 'talkSubject'])));
    }

    /** DELETE /api/talks/{id} */
    public function destroy(Talk $talk): JsonResponse
    {
        DB::transaction(function () use ($talk) {
            if ($talk->slide_path) {
                Storage::disk('s3')->delete($talk->slide_path);
            }
            $talk->speakers()->detach();
            $talk->delete();
        });

        return response()->json(null, 204);
    }

    private function format(Talk $t): array
    {
        return [
            'id'           => $t->id,
            'title'        => $t->title,
            'description'  => $t->description,
            'kind'         => $t->kind,
            'confirmed'    => (bool) $t->confirmed,
            'room'         => $t->room,
            'scheduled_at' => $t->scheduled_at,
            'edition_id'   => $t->edition_id,
            'subject'      => $t->talkSubject?->name,
            'slide_url'    => $t->slide_path
                ? Storage::disk('s3')->url($t->slide_path)
                : null,
            'speakers' => $t->speakers->map(fn($s) => [
                'id'        => $s->id,
                'name'      => $s->person->name,
                'email'     => $s->person->email,
                'photo_url' => $s->photo_path
                    ? Storage::disk('s3')->url($s->photo_path)
                    : null,
            ])->toArray(),
        ];
    }
}
