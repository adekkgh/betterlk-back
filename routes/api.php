<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HomeworkController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    // public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('2fa/verify', [AuthController::class, 'verifyTwoFactor']);
        Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',  [AuthController::class, 'resetPassword']);
    });

    Route::get('/health-check', function() {
        return response()->json([
            'status' => 'active',
            'service' => 'betterlk-back',
            'version' => 'v1',
            'timestamp' => now(),
        ]);
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
        });

        Route::prefix('groups')->group(function () {
            Route::get('/', [GroupController::class, 'index']);
            Route::post('/', [GroupController::class, 'store']);
            Route::get('/{id}', [GroupController::class, 'show']);
            Route::put('/{id}', [GroupController::class, 'update']);
            Route::delete('/{id}', [GroupController::class, 'destroy']);
        });

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::put('/{id}/role', [UserController::class, 'updateRole']);
        });

        Route::get('/roles', function () {
            return response()->json([
                'data' => \App\Models\Role::all()
            ]);
        });
    });
});
