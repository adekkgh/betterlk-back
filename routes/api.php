<?php

use App\Http\Controllers\Api\V1\AuthController;
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
    });
});
