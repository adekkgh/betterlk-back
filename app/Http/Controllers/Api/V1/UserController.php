<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProfessorGroup;
use App\Models\ProfessorProfile;
use App\Models\Role;
use App\Models\StudentProfile;
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

        $users = User::with([
            'role',
            'studentProfile.group',
            'professorProfile',
        ])
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
                // Группы преподавателя — массив
                'professor_groups'  => $user->role?->name === 'professor'
                    ? ProfessorGroup::where('professor_id', $user->id)
                        ->with('group')
                        ->get()
                        ->pluck('group')
                        ->filter()
                        ->values()
                    : null,
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

    // PUT /api/v1/users/{id}/group — изменить группу пользователя
    public function updateGroup(Request $request, int $id): JsonResponse
    {
        if (!$this->isAdmin($request) && !$request->user()?->hasRole('moderator')) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $user = User::with('role')->findOrFail($id);
        $roleName = $user->role?->name;

        if (!in_array($roleName, ['student', 'professor'])) {
            return response()->json([
                'message' => 'Группу можно назначить только студентам и преподавателям.',
            ], 422);
        }

        if ($roleName === 'student') {
            $request->validate([
                'group_id' => ['nullable', 'exists:groups,id'],
            ]);

            if ($request->group_id) {
                StudentProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    ['group_id' => $request->group_id]
                );
            } else {
                StudentProfile::where('user_id', $user->id)->delete();
            }
        }

        if ($roleName === 'professor') {
            $request->validate([
                'group_ids' => ['nullable', 'array'],
                'group_ids.*' => ['exists:groups,id'],
            ]);

            ProfessorProfile::firstOrCreate(['user_id' => $user->id]);

            // Полностью заменяем список групп
            ProfessorGroup::where('professor_id', $user->id)->delete();

            if (!empty($request->group_ids)) {
                foreach ($request->group_ids as $groupId) {
                    ProfessorGroup::create([
                        'professor_id' => $user->id,
                        'group_id'     => $groupId,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Группа обновлена.',
            'data'    => $user->fresh()->load(['role', 'studentProfile.group']),
        ]);
    }
}
