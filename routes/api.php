<?php

declare(strict_types=1);
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Welcome to the Bumpa API v1']);
    });

    Route::middleware('throttle:short')->prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('refresh', [AuthController::class, 'refreshToken']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });
        Route::apiResource('users', UserController::class);
    });
});
