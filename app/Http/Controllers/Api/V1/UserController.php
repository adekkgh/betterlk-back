<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private function isAdmin(Request $request): bool
    {
        return $request->user()?->hasRole('admin');
    }

    // GET /api/v1/users — список всех пользователей
    public function index(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $users = User::with(['role', 'studentProfile.group', 'professorProfile'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($user) => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'is_active'         => $user->is_active ?? true,
                'created_at'        => $user->created_at,
                'role'              => $user->role,
                'group'             => $user->studentProfile?->group?->name,
            ]);

        return response()->json(['data' => $users]);
    }

    // PUT /api/v1/users/{id}/role — изменить роль пользователя
    public function updateRole(Request $request, int $id): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        // Нельзя менять роль самому себе
        if ($request->user()->id === $id) {
            return response()->json(['message' => 'Нельзя изменить собственную роль.'], 422);
        }

        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($id);
        $role = Role::where('name', $request->role)->firstOrFail();

        $user->update(['role_id' => $role->id]);

        return response()->json([
            'message' => 'Роль обновлена.',
            'data'    => $user->fresh()->load('role'),
        ]);
    }
}
