<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $badges = Badge::with('users')->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Badges retrieved successfully',
            'data' => [
                'items' => $badges,
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
            'requirements' => 'required|array',
            'icon' => 'required|string|max:255',
            'tier' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $badge = Badge::create($validated);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Badge created successfully',
            'data' => [
                'item' => $badge,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Badge $badge): JsonResponse
    {
        $badge->load('users');

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Badge retrieved successfully',
            'data' => [
                'item' => $badge,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Badge $badge): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'requirements' => 'sometimes|array',
            'icon' => 'sometimes|string|max:255',
            'tier' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $badge->update($validated);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Badge updated successfully',
            'data' => [
                'item' => $badge,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Badge $badge): JsonResponse
    {
        $badge->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Badge deleted successfully',
            'data' => [],
        ]);
    }
}
