<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Specialization;
use Illuminate\Http\JsonResponse;

class SpecializationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Specialization::orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:specializations,name']]);

        $spec = Specialization::create(['name' => $request->name]);
        return response()->json(['message' => 'Специальность создана.', 'data' => $spec], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        $spec = Specialization::findOrFail($id);
        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:specializations,name,' . $id]]);
        $spec->update(['name' => $request->name]);

        return response()->json(['message' => 'Специальность обновлена.', 'data' => $spec]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()?->hasAnyRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Недостаточно прав.'], 403);
        }

        Specialization::findOrFail($id)->delete();
        return response()->json(['message' => 'Специальность удалена.']);
    }
}
