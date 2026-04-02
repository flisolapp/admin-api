<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collaborator;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CollaboratorController extends Controller
{
    /** GET /api/records/collaborators */
    public function index(Request $request): JsonResponse
    {
        $collaborators = Collaborator::with('person')
            ->when($request->edition_id, fn($q, $id) => $q->where('edition_id', $id))
            ->orderByDesc('id')
            ->get()
            ->map(fn($c) => $this->format($c));

        return response()->json($collaborators);
    }

    /** GET /api/records/collaborators/{id} */
    public function show(Collaborator $collaborator): JsonResponse
    {
        $collaborator->load('person');
        return response()->json($this->format($collaborator));
    }

    /** POST /api/records/collaborators */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:200'],
            'email'      => ['required', 'email', 'max:121'],
            'phone'      => ['required', 'string', 'max:20'],
            'cpf'        => ['nullable', 'string', 'max:14'],
            'role'       => ['required', 'string', 'max:80'],
            'edition_id' => ['required', 'exists:editions,id'],
        ]);

        $collaborator = DB::transaction(function () use ($data) {
            $person = Person::create([
                'name'  => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'cpf'   => $data['cpf'] ?? null,
            ]);

            return Collaborator::create([
                'person_id'  => $person->id,
                'edition_id' => $data['edition_id'],
                'role'       => $data['role'],
                'confirmed'  => false,
            ]);
        });

        return response()->json($this->format($collaborator->load('person')), 201);
    }

    /** PUT/PATCH /api/records/collaborators/{id} */
    public function update(Request $request, Collaborator $collaborator): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:200'],
            'email' => ['sometimes', 'email', 'max:121'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'cpf'   => ['nullable', 'string', 'max:14'],
            'role'  => ['sometimes', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($data, $collaborator) {
            $personFields = array_filter([
                'name'  => $data['name']  ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'cpf'   => $data['cpf']   ?? null,
            ]);
            if ($personFields) {
                $collaborator->person->update($personFields);
            }
            if (isset($data['role'])) {
                $collaborator->update(['role' => $data['role']]);
            }
        });

        return response()->json($this->format($collaborator->load('person')));
    }

    /** PATCH /api/records/collaborators/{id}/confirm */
    public function confirm(Request $request, Collaborator $collaborator): JsonResponse
    {
        $data = $request->validate(['confirmed' => ['required', 'boolean']]);
        $collaborator->update($data);

        return response()->json($this->format($collaborator->load('person')));
    }

    /** DELETE /api/records/collaborators/{id} */
    public function destroy(Collaborator $collaborator): JsonResponse
    {
        $collaborator->delete();
        return response()->json(null, 204);
    }

    private function format(Collaborator $c): array
    {
        return [
            'id'         => $c->id,
            'name'       => $c->person->name,
            'email'      => $c->person->email,
            'phone'      => $c->person->phone,
            'cpf'        => $c->person->cpf,
            'role'       => $c->role,
            'confirmed'  => (bool) $c->confirmed,
            'edition_id' => $c->edition_id,
        ];
    }
}
