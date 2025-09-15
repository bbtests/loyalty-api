<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\CashbackPaymentController;
use App\Http\Controllers\LoyaltyPointController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->prefix('v1')->group(function () {
    // API Health Check
    Route::get('/', function () {
        return response()->json([
            'message' => 'Welcome to the Bumpa API v1',
            'version' => '1.0.0',
            'status' => 'active',
        ]);
    });

    // Authentication Routes (Public)
    Route::middleware('throttle:short')->prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Authentication Management
        Route::prefix('auth')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('refresh', [AuthController::class, 'refreshToken']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });

        // Payment Routes
        Route::prefix('payments')->group(function () {
            Route::get('/configuration', [PaymentController::class, 'getConfiguration']);
            Route::get('/providers', [PaymentController::class, 'getProviders']);
            Route::get('/public-key', [PaymentController::class, 'getPublicKey']);
            Route::post('/initialize', [PaymentController::class, 'initializePayment']);
            Route::post('/verify', [PaymentController::class, 'verifyPayment']);
            Route::post('/process-purchase', [PaymentController::class, 'processPurchaseAfterPayment']);
            Route::post('/cashback', [PaymentController::class, 'processCashback']);
        });

        // Resource Routes
        Route::apiResource('users', UserController::class);
        Route::apiResource('achievements', AchievementController::class);
        Route::apiResource('badges', BadgeController::class);

        // Achievement simulation endpoint
        Route::post('achievements/simulate', [AchievementController::class, 'simulate']);
        Route::apiResource('loyalty-points', LoyaltyPointController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('cashback-payments', CashbackPaymentController::class);
        Route::apiResource('roles', RoleController::class)->only(['index', 'show']);

        // User-specific Routes
        Route::prefix('users/{user}')->group(function () {
            Route::get('/achievements', [LoyaltyPointController::class, 'getUserAchievements']);
            Route::get('/transactions', [TransactionController::class, 'getUserTransactions']);
            Route::get('/cashback-payments', [CashbackPaymentController::class, 'getUserCashbackPayments']);
            Route::post('/redeem-points', [LoyaltyPointController::class, 'redeemPoints']);
        });

        // Cashback Processing
        Route::post('/cashback/process', [CashbackPaymentController::class, 'process']);

        // Webhook Routes
        Route::post('/webhooks/payment', [TransactionController::class, 'handlePaymentWebhook']);

        // Admin Routes
        Route::middleware(['role:admin'])->prefix('admin')->group(function () {
            Route::get('/users/achievements', [UserController::class, 'getAllUsersAchievements']);
            Route::get('/users/{user}/loyalty-data', [UserController::class, 'getUserLoyaltyData']);
            Route::get('/analytics/loyalty-stats', [LoyaltyPointController::class, 'getLoyaltyStats']);
        });
    });
});
