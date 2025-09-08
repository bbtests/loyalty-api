<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminUserCollection;
use App\Http\Resources\LoyaltyDataResource;
use App\Http\Resources\LoyaltyStatsResource;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Get all users' achievements and badge progress
     * GET /api/admin/users/achievements
     */
    public function getAllUsersAchievements(): JsonResponse
    {
        try {
            $users = User::with([
                'loyaltyPoints',
                'achievements',
                'badges' => function ($query) {
                    $query->orderBy('tier', 'desc');
                },
            ])->paginate(20);

            return response()->json([
                'success' => true,
                'data' => new AdminUserCollection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific user's loyalty data
     * GET /api/admin/users/{user}/loyalty-data
     */
    public function getUserLoyaltyData(User $user): JsonResponse
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
                'message' => 'Failed to retrieve user loyalty data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loyalty program analytics
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

            return response()->json([
                'success' => true,
                'data' => new LoyaltyStatsResource($stats),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve loyalty statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
