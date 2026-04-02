<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ParticipantController extends Controller
{
    /** GET /api/records/participants */
    public function index(Request $request): JsonResponse
    {
        $query = Participant::with('person')
            ->when($request->edition_id, fn($q, $id) => $q->where('edition_id', $id))
            ->orderByDesc('id');

        $participants = $query->get()->map(fn($p) => $this->format($p));

        return response()->json($participants);
    }

    /** GET /api/records/participants/{id} */
    public function show(Participant $participant): JsonResponse
    {
        $participant->load('person');
        return response()->json($this->format($participant));
    }

    /** POST /api/records/participants */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:200'],
            'email'      => ['required', 'email', 'max:121'],
            'phone'      => ['required', 'string', 'max:20'],
            'cpf'        => ['nullable', 'string', 'max:14'],
            'edition_id' => ['required', 'exists:editions,id'],
        ]);

        $participant = DB::transaction(function () use ($data) {
            $person = Person::create([
                'name'  => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'cpf'   => $data['cpf'] ?? null,
            ]);

            return Participant::create([
                'person_id'  => $person->id,
                'edition_id' => $data['edition_id'],
                'confirmed'  => false,
            ]);
        });

        return response()->json($this->format($participant->load('person')), 201);
    }

    /** PUT/PATCH /api/records/participants/{id} */
    public function update(Request $request, Participant $participant): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:200'],
            'email' => ['sometimes', 'email', 'max:121'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'cpf'   => ['nullable', 'string', 'max:14'],
        ]);

        DB::transaction(function () use ($data, $participant) {
            $participant->person->update(array_filter($data));
        });

        return response()->json($this->format($participant->load('person')));
    }

    /** PATCH /api/records/participants/{id}/confirm */
    public function confirm(Request $request, Participant $participant): JsonResponse
    {
        $data = $request->validate(['confirmed' => ['required', 'boolean']]);
        $participant->update($data);

        return response()->json($this->format($participant->load('person')));
    }

    /** DELETE /api/records/participants/{id} */
    public function destroy(Participant $participant): JsonResponse
    {
        $participant->delete();
        return response()->json(null, 204);
    }

    private function format(Participant $p): array
    {
        return [
            'id'         => $p->id,
            'name'       => $p->person->name,
            'email'      => $p->person->email,
            'phone'      => $p->person->phone,
            'cpf'        => $p->person->cpf,
            'confirmed'  => (bool) $p->confirmed,
            'edition_id' => $p->edition_id,
        ];
    }
}
