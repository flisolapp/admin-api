<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Collaborator;
use App\Models\Talk;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConfirmationController extends Controller
{
    /** GET /api/confirmation/participants */
    public function participants(Request $request): JsonResponse
    {
        $items = Participant::with('person')
            ->when($request->edition_id, fn($q, $id) => $q->where('edition_id', $id))
            ->orderBy('confirmed')
            ->orderBy('id')
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->person->name,
                'email'     => $p->person->email,
                'phone'     => $p->person->phone,
                'confirmed' => (bool) $p->confirmed,
            ]);

        return response()->json($items);
    }

    /** GET /api/confirmation/collaborators */
    public function collaborators(Request $request): JsonResponse
    {
        $items = Collaborator::with('person')
            ->when($request->edition_id, fn($q, $id) => $q->where('edition_id', $id))
            ->orderBy('confirmed')
            ->orderBy('id')
            ->get()
            ->map(fn($c) => [
                'id'        => $c->id,
                'name'      => $c->person->name,
                'email'     => $c->person->email,
                'phone'     => $c->person->phone,
                'confirmed' => (bool) $c->confirmed,
            ]);

        return response()->json($items);
    }

    /** GET /api/confirmation/talks */
    public function talks(Request $request): JsonResponse
    {
        $items = Talk::with('speakers.person')
            ->when($request->edition_id, fn($q, $id) => $q->where('edition_id', $id))
            ->orderBy('confirmed')
            ->orderBy('id')
            ->get()
            ->map(fn($t) => [
                'id'        => $t->id,
                'title'     => $t->title,
                'speakers'  => $t->speakers->map(fn($s) => $s->person->name)->implode(', '),
                'confirmed' => (bool) $t->confirmed,
            ]);

        return response()->json($items);
    }
}
