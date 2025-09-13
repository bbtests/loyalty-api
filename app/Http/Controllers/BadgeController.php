<?php

namespace App\Http\Controllers;

use App\Http\Resources\Badge\BadgeResource;
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
        try {
            $badges = Badge::with('users')->get();

            return $this->successItems(
                $badges,
                BadgeResource::class,
                'Badges retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve badges', 500, [
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
                'requirements' => 'required|array',
                'icon' => 'required|string|max:255',
                'tier' => 'required|integer|min:1',
                'is_active' => 'boolean',
            ]);

            $badge = Badge::create($validated);

            return $this->successItem(
                new BadgeResource($badge),
                'Badge created successfully.',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create badge', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Badge $badge): JsonResponse
    {
        try {
            $badge->load('users');

            return $this->successItem(
                new BadgeResource($badge),
                'Badge retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve badge', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Badge $badge): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'requirements' => 'sometimes|array',
                'icon' => 'sometimes|string|max:255',
                'tier' => 'sometimes|integer|min:1',
                'is_active' => 'sometimes|boolean',
            ]);

            $badge->update($validated);

            return $this->successItem(
                new BadgeResource($badge),
                'Badge updated successfully.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update badge', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Badge $badge): JsonResponse
    {
        try {
            $badge->delete();

            return $this->successMessage('Badge deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete badge', 500, [
                $e->getMessage(),
            ]);
        }
    }
}
