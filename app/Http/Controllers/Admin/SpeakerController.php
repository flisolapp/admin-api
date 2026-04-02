<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Speaker;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpeakerController extends Controller
{
    /** GET /api/records/speakers */
    public function index(Request $request): JsonResponse
    {
        $speakers = Speaker::with(['person', 'talks'])
            ->when($request->edition_id, fn($q, $id) => $q->where('edition_id', $id))
            ->orderByDesc('id')
            ->get()
            ->map(fn($s) => $this->format($s));

        return response()->json($speakers);
    }

    /** GET /api/records/speakers/{id} */
    public function show(Speaker $speaker): JsonResponse
    {
        $speaker->load(['person', 'talks']);
        return response()->json($this->format($speaker));
    }

    /** POST /api/records/speakers */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:200'],
            'email'      => ['required', 'email', 'max:121'],
            'phone'      => ['required', 'string', 'max:20'],
            'cpf'        => ['nullable', 'string', 'max:14'],
            'edition_id' => ['required', 'exists:editions,id'],
            'photo'      => ['nullable', 'image', 'max:4096'],
        ]);

        $speaker = DB::transaction(function () use ($data, $request) {
            $person = Person::create([
                'name'  => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'cpf'   => $data['cpf'] ?? null,
            ]);

            $speaker = Speaker::create([
                'person_id'  => $person->id,
                'edition_id' => $data['edition_id'],
                'confirmed'  => false,
            ]);

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store(
                    "speakers/{$speaker->id}/photo", 's3'
                );
                $speaker->update(['photo_path' => $path]);
            }

            return $speaker;
        });

        return response()->json($this->format($speaker->load(['person', 'talks'])), 201);
    }

    /** PUT/PATCH /api/records/speakers/{id} */
    public function update(Request $request, Speaker $speaker): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:200'],
            'email' => ['sometimes', 'email', 'max:121'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'cpf'   => ['nullable', 'string', 'max:14'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        DB::transaction(function () use ($data, $request, $speaker) {
            $speaker->person->update(array_filter([
                'name'  => $data['name']  ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'cpf'   => $data['cpf']   ?? null,
            ]));

            if ($request->hasFile('photo')) {
                // Remove old photo
                if ($speaker->photo_path) {
                    Storage::disk('s3')->delete($speaker->photo_path);
                }
                $path = $request->file('photo')->store(
                    "speakers/{$speaker->id}/photo", 's3'
                );
                $speaker->update(['photo_path' => $path]);
            }
        });

        return response()->json($this->format($speaker->load(['person', 'talks'])));
    }

    /** DELETE /api/records/speakers/{id} */
    public function destroy(Speaker $speaker): JsonResponse
    {
        DB::transaction(function () use ($speaker) {
            if ($speaker->photo_path) {
                Storage::disk('s3')->delete($speaker->photo_path);
            }
            $speaker->delete();
        });

        return response()->json(null, 204);
    }

    private function format(Speaker $s): array
    {
        return [
            'id'         => $s->id,
            'name'       => $s->person->name,
            'email'      => $s->person->email,
            'phone'      => $s->person->phone,
            'cpf'        => $s->person->cpf,
            'confirmed'  => (bool) $s->confirmed,
            'edition_id' => $s->edition_id,
            'photo_url'  => $s->photo_path ? Storage::disk('s3')->url($s->photo_path) : null,
            'talks'      => $s->talks->map(fn($t) => [
                'id'    => $t->id,
                'title' => $t->title,
                'kind'  => $t->kind,
            ])->toArray(),
        ];
    }
}
