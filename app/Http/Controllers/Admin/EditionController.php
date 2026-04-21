<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EditionController extends Controller
{
    /** GET /api/editions */
    public function index(): JsonResponse
    {
        $editions = Edition::orderByDesc('id')->get();

        return response()->json($editions);
    }

    /** GET /api/editions/{id} */
    public function show(Edition $edition): JsonResponse
    {
        return response()->json($edition);
    }

    /** POST /api/editions */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'active' => ['boolean'],
        ]);

        $edition = DB::transaction(function () use ($data) {
            if (!empty($data['active'])) {
                // Only one edition can be active at a time
                Edition::where('active', true)->update(['active' => false]);
            }
            return Edition::create($data);
        });

        return response()->json($edition, 201);
    }

    /** PUT/PATCH /api/editions/{id} */
    public function update(Request $request, Edition $edition): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'city' => ['sometimes', 'string', 'max:100'],
            'date' => ['sometimes', 'date'],
            'active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $edition) {
            if (!empty($data['active'])) {
                Edition::where('active', true)
                    ->where('id', '!=', $edition->id)
                    ->update(['active' => false]);
            }
            $edition->update($data);
        });

        return response()->json($edition->fresh());
    }

    /** DELETE /api/editions/{id} */
    public function destroy(Edition $edition): JsonResponse
    {
        $edition->delete();

        return response()->json(null, 204);
    }
}
