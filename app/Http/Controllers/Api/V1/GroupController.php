<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    // GET /api/v1/groups — список всех групп
    public function index(): JsonResponse
    {
        $groups = Group::with('specialization')
            ->orderBy('name')
            ->get();

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
}
