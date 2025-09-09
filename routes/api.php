<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CashbackController;
use App\Http\Controllers\CashbackPaymentController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\LoyaltyPointController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Test routes (no prefix, no auth for testing)
Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/users/{user}/achievements', [LoyaltyController::class, 'getUserAchievements']);
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/users/achievements', [AdminController::class, 'getAllUsersAchievements']);

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

        // Admin routes
        Route::middleware(['role:admin'])->prefix('admin')->group(function () {
            Route::get('/users/achievements', [AdminController::class, 'getAllUsersAchievements']);
            Route::get('/users/{user}/loyalty-data', [AdminController::class, 'getUserLoyaltyData']);
            Route::get('/analytics/loyalty-stats', [AdminController::class, 'getLoyaltyStats']);
        });

        // Public routes for testing
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('achievements', AchievementController::class);
        Route::apiResource('badges', BadgeController::class);
        Route::apiResource('loyalty-points', LoyaltyPointController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('cashback-payments', CashbackPaymentController::class);
        Route::apiResource('roles', RoleController::class)->only(['index', 'show']);
        Route::get('/users/{user}/achievements', [LoyaltyController::class, 'getUserAchievements']);
        Route::post('/users/{user}/redeem-points', [LoyaltyController::class, 'redeemPoints']);
        Route::post('/cashback/process', [CashbackController::class, 'process']);

        // Webhook routes (for external payment providers)
        Route::post('/webhooks/payment', [TransactionController::class, 'handlePaymentWebhook']);

    });
});
