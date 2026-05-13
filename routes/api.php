<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HomeworkController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\JournalController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\SpecializationController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    Route::get('/health-check', function() {
        return response()->json([
            'status' => 'active',
            'service' => 'betterlk-back',
            'version' => 'v1',
            'timestamp' => now(),
        ]);
    });

    // public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('2fa/verify', [AuthController::class, 'verifyTwoFactor']);
        Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',  [AuthController::class, 'resetPassword']);
    });

    // authorized routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::prefix('homeworks')->group(function () {
            Route::get('/', [HomeworkController::class, 'index']);
            Route::post('/', [HomeworkController::class, 'store']);
            Route::get('/{id}', [HomeworkController::class, 'show']);
            Route::put('/{id}', [HomeworkController::class, 'update']);
            Route::delete('/{id}', [HomeworkController::class, 'destroy']);
            Route::post('/{id}/submit', [HomeworkController::class, 'submit']);
        });

        Route::prefix('submissions')->group(function () {
            Route::post('/{id}/check', [HomeworkController::class, 'check']);
            Route::delete('/{submissionId}/files/{fileId}', [HomeworkController::class, 'deleteFile']);
            Route::post('/{id}/recheck', [HomeworkController::class, 'recheck']);
        });

        Route::prefix('groups')->group(function () {
            Route::get('/', [GroupController::class, 'index']);
            Route::post('/', [GroupController::class, 'store']);
            Route::get('/students-without-group', [GroupController::class, 'studentsWithoutGroup']);
            Route::get('/{id}', [GroupController::class, 'show']);
            Route::put('/{id}', [GroupController::class, 'update']);
            Route::delete('/{id}', [GroupController::class, 'destroy']);
            Route::post('/{id}/students', [GroupController::class, 'addStudents']);
            Route::delete('/{id}/students', [GroupController::class, 'removeStudents']);
        });

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::put('/{id}/role', [UserController::class, 'updateRole']);
            Route::put('/{id}/group', [UserController::class, 'updateGroup']);
        });

        Route::prefix('subjects')->group(function () {
            Route::get('/', [SubjectController::class, 'index']);
            Route::post('/', [SubjectController::class, 'store']);
            Route::put('/{id}', [SubjectController::class, 'update']);
            Route::delete('/{id}', [SubjectController::class, 'destroy']);
        });

        Route::prefix('journals')->group(function () {
            Route::get('/', [JournalController::class, 'index']);
            Route::post('/', [JournalController::class, 'store']);
            Route::get('/{id}', [JournalController::class, 'show']);
            Route::delete('/{id}', [JournalController::class, 'destroy']);
            Route::post('/{id}/entry', [JournalController::class, 'upsertEntry']);
            Route::post('/{id}/entries/batch', [JournalController::class, 'batchEntries']);
            Route::post('/{id}/rating', [JournalController::class, 'upsertRating']);
        });

        Route::prefix('specializations')->group(function () {
            Route::get('/', [SpecializationController::class, 'index']);
            Route::post('/', [SpecializationController::class, 'store']);
            Route::put('/{id}', [SpecializationController::class, 'update']);
            Route::delete('/{id}', [SpecializationController::class, 'destroy']);
        });

        Route::prefix('news')->group(function () {
            Route::get('/', [NewsController::class, 'index']);
            Route::get('/{id}', [NewsController::class, 'show']);
            Route::post('/', [NewsController::class, 'store']);
            Route::delete('/{id}', [NewsController::class, 'destroy']);
        });

        Route::get('/roles', function () {
            return response()->json([
                'data' => \App\Models\Role::all()
            ]);
        });

        Route::get('/users/{id}/subjects', [UserController::class, 'getSubjects']);
        Route::put('/users/{id}/subjects', [UserController::class, 'updateSubjects']);

        Route::get('/me/subjects', function (Request $request) {
            $user = $request->user();
            if (!$user) return response()->json(['message' => 'Не авторизован.'], 401);

            if ($user->hasAnyRole(['admin', 'moderator'])) {
                // Админ видит все предметы
                return response()->json(['data' => \App\Models\Subject::orderBy('name')->get()]);
            }

            // Препод видит только свои предметы
            $subjects = \App\Models\ProfessorSubjectAssignment::where('professor_id', $user->id)
                ->with('subject')
                ->get()
                ->map(fn($a) => $a->subject)
                ->filter()
                ->values();

            return response()->json(['data' => $subjects]);
        });
    });
});
