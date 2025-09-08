<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyPointController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $loyaltyPoints = LoyaltyPoint::with('user')->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Loyalty points retrieved successfully',
            'data' => [
                'items' => $loyaltyPoints,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:0',
            'total_earned' => 'required|integer|min:0',
            'total_redeemed' => 'required|integer|min:0',
        ]);

        $loyaltyPoint = LoyaltyPoint::create($validated);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'message' => 'Loyalty point created successfully',
            'data' => [
                'item' => $loyaltyPoint,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        $loyaltyPoint->load('user');

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Loyalty point retrieved successfully',
            'data' => [
                'item' => $loyaltyPoint,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'points' => 'sometimes|integer|min:0',
            'total_earned' => 'sometimes|integer|min:0',
            'total_redeemed' => 'sometimes|integer|min:0',
        ]);

        $loyaltyPoint->update($validated);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Loyalty point updated successfully',
            'data' => [
                'item' => $loyaltyPoint,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        $loyaltyPoint->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Loyalty point deleted successfully',
            'data' => [],
        ]);
    }
}
