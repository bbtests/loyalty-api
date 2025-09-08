<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $achievements = Achievement::with('users')->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Achievements retrieved successfully',
            'data' => [
                'items' => $achievements,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'points_required' => 'required|integer|min:0',
            'badge_icon' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $achievement = Achievement::create($validated);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Achievement created successfully',
            'data' => [
                'item' => $achievement,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Achievement $achievement): JsonResponse
    {
        $achievement->load('users');

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Achievement retrieved successfully',
            'data' => [
                'item' => $achievement,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Achievement $achievement): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'points_required' => 'sometimes|integer|min:0',
            'badge_icon' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $achievement->update($validated);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Achievement updated successfully',
            'data' => [
                'item' => $achievement,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Achievement $achievement): JsonResponse
    {
        $achievement->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Achievement deleted successfully',
            'data' => [],
        ]);
    }
}
