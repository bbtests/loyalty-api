<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoyaltyPoint\RedeemPointsRequest;
use App\Http\Resources\LoyaltyDataResource;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;

class LoyaltyController extends Controller
{
    private LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Get user's achievements and badge progress
     * GET /api/users/{user}/achievements
     */
    public function getUserAchievements(User $user): JsonResponse
    {
        try {
            $loyaltyData = $this->loyaltyService->getUserLoyaltyData($user);

            return response()->json([
                'success' => true,
                'data' => new LoyaltyDataResource($loyaltyData),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user achievements',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Redeem loyalty points
     * POST /api/users/{user}/redeem-points
     */
    public function redeemPoints(RedeemPointsRequest $request, User $user): JsonResponse
    {
        try {
            $points = $request->validated()['points'];

            $success = $this->loyaltyService->redeemPoints($user, $points);

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient points for redemption',
                ], 400);
            }

            // Get updated loyalty data
            $loyaltyData = $this->loyaltyService->getUserLoyaltyData($user);

            return response()->json([
                'success' => true,
                'message' => "Successfully redeemed {$points} points",
                'data' => new LoyaltyDataResource($loyaltyData),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem points',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
