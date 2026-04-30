<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Subject::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:subjects,name']]);

        $subject = Subject::create(['name' => $request->name]);

        return response()->json(['message' => 'Предмет создан.', 'data' => $subject], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $subject = Subject::findOrFail($id);
        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:subjects,name,' . $id]]);
        $subject->update(['name' => $request->name]);

        return response()->json(['message' => 'Предмет обновлён.', 'data' => $subject]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        Subject::findOrFail($id)->delete();
        return response()->json(['message' => 'Предмет удалён.']);
    }
}
