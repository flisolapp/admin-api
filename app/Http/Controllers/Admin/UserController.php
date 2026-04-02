<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** GET /api/records/users */
    public function index(): JsonResponse
    {
        $users = User::orderByDesc('id')
            ->get()
            ->map(fn($u) => $this->format($u));

        return response()->json($users);
    }

    /** GET /api/records/users/{id} */
    public function show(User $user): JsonResponse
    {
        return response()->json($this->format($user));
    }

    /** POST /api/records/users */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:200'],
            'email'    => ['required', 'email', 'max:121', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', Rule::in(['super_admin', 'organizer', 'credential'])],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);

        return response()->json($this->format($user), 201);
    }

    /** PUT/PATCH /api/records/users/{id} */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:200'],
            'email'    => ['sometimes', 'email', 'max:121', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role'     => ['sometimes', Rule::in(['super_admin', 'organizer', 'credential'])],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json($this->format($user->fresh()));
    }

    /** DELETE /api/records/users/{id} */
    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Não é possível excluir o próprio usuário.'], 422);
        }

        $user->delete();
        return response()->json(null, 204);
    }

    private function format(User $u): array
    {
        return [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'role'       => $u->role,
            'last_login' => $u->last_login_at?->diffForHumans(),
        ];
    }
}
