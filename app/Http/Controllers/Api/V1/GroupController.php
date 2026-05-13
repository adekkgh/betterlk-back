<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    // GET /api/v1/groups — список всех групп
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('role');

        $query = Group::with(['specialization', 'students.user']);

        // professor only gets groups he's assigned to
        if ($user->hasRole('professor')) {
            $query->whereHas('professors', function ($q) use ($user) {
                $q->where('professor_id', $user->id);
            });
        }

        $groups = $query->orderBy('name')->get()->map(fn($group) => [
            'id'             => $group->id,
            'name'           => $group->name,
            'course'         => $group->course,
            'specialization' => $group->specialization,
            'students_count' => $group->students->count(),
            'students'       => $group->students->map(fn($sp) => [
                'id'    => $sp->user->id,
                'name'  => $sp->user->name,
                'email' => $sp->user->email,
            ]),
        ]);

        return response()->json(['data' => $groups]);
    }

    // GET /api/v1/groups/{id} — одна группа
    public function show(int $id): JsonResponse
    {
        $group = Group::with(['specialization', 'students.user'])
            ->findOrFail($id);

        return response()->json(['data' => $group]);
    }

    // POST /api/v1/groups — создать группу (admin/moderator)
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'course'             => ['required', 'integer', 'min:1', 'max:6'],
            'specialization_id'  => ['required', 'exists:specializations,id'],
        ]);

        $group = Group::create($request->only(['name', 'course', 'specialization_id']));

        return response()->json([
            'message' => 'Группа создана.',
            'data'    => $group->load('specialization'),
        ], 201);
    }

    // PUT /api/v1/groups/{id} — обновить группу
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $group = Group::findOrFail($id);

        $request->validate([
            'name'              => ['sometimes', 'string', 'max:255'],
            'course'            => ['sometimes', 'integer', 'min:1', 'max:6'],
            'specialization_id' => ['sometimes', 'exists:specializations,id'],
        ]);

        $group->update($request->only(['name', 'course', 'specialization_id']));

        return response()->json([
            'message' => 'Группа обновлена.',
            'data'    => $group->load('specialization'),
        ]);
    }

    // DELETE /api/v1/groups/{id} — удалить группу
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        Group::findOrFail($id)->delete();

        return response()->json(['message' => 'Группа удалена.']);
    }

    // TODO: remove and just add filter on index endpoint
    // GET /api/v1/groups/students-without-group
    public function studentsWithoutGroup(): JsonResponse
    {
        $students = User::whereHas('role', fn($q) => $q->where('name', 'student'))
            ->whereDoesntHave('studentProfile', fn($q) => $q->whereNotNull('group_id'))
            ->orWhereHas('studentProfile', fn($q) => $q->whereNull('group_id'))
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $students]);
    }

    // POST /api/v1/groups/{id}/students  body: { student_ids: [1,2,3] }
    public function addStudents(Request $request, int $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $request->validate(['student_ids' => ['required', 'array'], 'student_ids.*' => ['exists:users,id']]);

        foreach ($request->student_ids as $studentId) {
            StudentProfile::updateOrCreate(
                ['user_id' => $studentId],
                ['group_id' => $group->id]
            );
        }

        return response()->json(['message' => 'Студенты добавлены.']);
    }

    // DELETE /api/v1/groups/{id}/students  body: { student_ids: [1,2,3] }
    public function removeStudents(Request $request, int $id): JsonResponse
    {
        $request->validate(['student_ids' => ['required', 'array'], 'student_ids.*' => ['exists:users,id']]);

        StudentProfile::where('group_id', $id)
            ->whereIn('user_id', $request->student_ids)
            ->update(['group_id' => null]);

        return response()->json(['message' => 'Студенты удалены из группы.']);
    }
}
