<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoyaltyPoint\RedeemPointsRequest;
use App\Http\Resources\LoyaltyDataResource;
use App\Http\Resources\LoyaltyPoint\LoyaltyPointResource;
use App\Http\Resources\LoyaltyStatsResource;
use App\Models\LoyaltyPoint;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoyaltyPointController extends Controller
{
    private LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $loyaltyPoints = LoyaltyPoint::with('user')->get();

            return $this->successItems(
                $loyaltyPoints,
                LoyaltyPointResource::class,
                'Loyalty points retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve loyalty points', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'points' => 'required|integer|min:0',
                'total_earned' => 'required|integer|min:0',
                'total_redeemed' => 'required|integer|min:0',
            ]);

            $loyaltyPoint = LoyaltyPoint::create($validated);

            return $this->successItem(
                new LoyaltyPointResource($loyaltyPoint),
                'Loyalty point created successfully.',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create loyalty point', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        try {
            $loyaltyPoint->load('user');

            return $this->successItem(
                new LoyaltyPointResource($loyaltyPoint),
                'Loyalty point retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve loyalty point', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'points' => 'sometimes|integer|min:0',
                'total_earned' => 'sometimes|integer|min:0',
                'total_redeemed' => 'sometimes|integer|min:0',
            ]);

            $loyaltyPoint->update($validated);

            return $this->successItem(
                new LoyaltyPointResource($loyaltyPoint),
                'Loyalty point updated successfully.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update loyalty point', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        try {
            $loyaltyPoint->delete();

            return $this->successMessage('Loyalty point deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete loyalty point', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user's achievements and badge progress
     * GET /api/users/{user}/achievements
     */
    public function getUserAchievements(User $user): JsonResponse
    {
        try {
            $loyaltyData = $this->loyaltyService->getUserLoyaltyData($user);

            return $this->successItem(
                new LoyaltyDataResource($loyaltyData),
                'User achievements retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user achievements', 500, [
                $e->getMessage(),
            ]);
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
                return $this->errorResponse('Insufficient points for redemption', 400, [
                    'Insufficient loyalty points available for redemption.',
                ]);
            }

            // Get updated loyalty data
            $loyaltyData = $this->loyaltyService->getUserLoyaltyData($user);

            return $this->successItem(
                new LoyaltyDataResource($loyaltyData),
                "Successfully redeemed {$points} points"
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to redeem points', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Get loyalty program analytics (Admin only)
     * GET /api/admin/analytics/loyalty-stats
     */
    public function getLoyaltyStats(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::whereHas('loyaltyPoints')->count(),
                'total_points_issued' => DB::table('loyalty_points')->sum('total_earned'),
                'total_points_redeemed' => DB::table('loyalty_points')->sum('total_redeemed'),
                'total_transactions' => DB::table('transactions')->where('transaction_type', 'purchase')->count(),
                'total_revenue' => DB::table('transactions')->where('transaction_type', 'purchase')->sum('amount'),
                'achievements_unlocked' => DB::table('user_achievements')->count(),
                'badges_earned' => DB::table('user_badges')->count(),
                'badge_distribution' => DB::table('user_badges')
                    ->join('badges', 'user_badges.badge_id', '=', 'badges.id')
                    ->select('badges.name', 'badges.tier', DB::raw('count(*) as count'))
                    ->groupBy('badges.id', 'badges.name', 'badges.tier')
                    ->orderBy('badges.tier')
                    ->get(),
                'recent_achievements' => DB::table('user_achievements')
                    ->join('users', 'user_achievements.user_id', '=', 'users.id')
                    ->join('achievements', 'user_achievements.achievement_id', '=', 'achievements.id')
                    ->select('users.name as user_name', 'achievements.name as achievement_name', 'user_achievements.unlocked_at')
                    ->orderBy('user_achievements.unlocked_at', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            return $this->successItem(
                new LoyaltyStatsResource($stats),
                'Loyalty statistics retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve loyalty statistics', 500, [
                $e->getMessage(),
            ]);
        }
    }
}
