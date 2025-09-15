<?php

namespace App\Http\Controllers;

use App\Http\Resources\Achievement\AchievementResource;
use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $achievements = Achievement::with('users')->get();

            return $this->successItems(
                $achievements,
                AchievementResource::class,
                'Achievements retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve achievements', 500, [
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
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'points_required' => 'required|integer|min:0',
                'badge_icon' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $achievement = Achievement::create($validated);

            return $this->successItem(
                new AchievementResource($achievement),
                'Achievement created successfully.',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create achievement', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Achievement $achievement): JsonResponse
    {
        try {
            $achievement->load('users');

            return $this->successItem(
                new AchievementResource($achievement),
                'Achievement retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve achievement', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Achievement $achievement): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'points_required' => 'sometimes|integer|min:0',
                'badge_icon' => 'sometimes|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $achievement->update($validated);

            return $this->successItem(
                new AchievementResource($achievement),
                'Achievement updated successfully.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update achievement', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Achievement $achievement): JsonResponse
    {
        try {
            $achievement->delete();

            return $this->successMessage('Achievement deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete achievement', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Simulate unlocking a specific achievement for the authenticated user.
     */
    public function simulate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            $achievementId = $request->input('achievement_id');

            if (! $achievementId) {
                // If no specific achievement ID provided, get a random unlocked achievement
                $availableAchievements = Achievement::active()
                    ->whereNotIn('id', $user->achievements->pluck('id')->toArray())
                    ->get();

                if ($availableAchievements->isEmpty()) {
                    return $this->errorResponse('No available achievements to unlock', 400);
                }

                $achievement = $availableAchievements->random();
            } else {
                $achievement = Achievement::find($achievementId);

                if (! $achievement) {
                    return $this->errorResponse('Achievement not found', 404);
                }

                if (! $achievement->is_active) {
                    return $this->errorResponse('Achievement is not active', 400);
                }

                // Check if already unlocked
                if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                    return $this->errorResponse('Achievement already unlocked', 400);
                }
            }

            // Manually unlock the achievement
            $achievementService = new AchievementService;
            $achievementService->unlockAchievement($user, $achievement);

            return $this->successItem(
                new AchievementResource($achievement),
                "Achievement '{$achievement->name}' unlocked successfully!",
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to simulate achievement', 500, [
                $e->getMessage(),
            ]);
        }
    }
}
