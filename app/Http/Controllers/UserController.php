<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\LoyaltyDataResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

/**
 * @group User Management
 *
 * APIs for managing users, roles, permissions
 */
class UserController extends Controller
{
    private LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $pagination = $this::applyPagination($request);

            $users = User::with(['loyaltyPoints', 'achievements', 'badges'])
                ->orderBy($pagination['sort_by'], $pagination['sort_order'])
                ->paginate($pagination['per_page']);

            return $this->successCollection(
                $users,
                UserResource::class,
                'Users fetched successfully.',
                $this->buildFilters([
                    'search_query' => $request->input('search'),
                ])
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users', 422, [
                $e->getMessage(),
            ]);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $roleId = $validated['role_id'] ?? null;
            unset($validated['role_id']);

            // Create user
            $user = User::create($validated);

            // Assign role if provided
            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $user->assignRole($role);
                }
            }

            // Load relationships for the response
            $user->load(['roles', 'loyaltyPoints', 'achievements', 'badges']);

            return $this->successItem(
                new UserResource($user),
                'User created successfully.',
                201,
                []
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create user', 422, [
                $e->getMessage(),
            ]);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $this->authorize('view', Auth::user());

            $user = User::with(['loyaltyPoints', 'achievements', 'badges'])->find($id);

            if (! $user) {
                return $this->notFoundError('User', $id);
            }

            return $this->successItem(
                new UserResource($user),
                'User retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user', 422, [
                $e->getMessage(),
            ]);
        }
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return $this->notFoundError('User', $id);
        }
        $validated = $request->validated();
        $roleId = $validated['role_id'] ?? null;
        unset($validated['role_id']);

        $user->update($validated);

        // Update role if provided
        if ($roleId !== null) {
            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $user->syncRoles([$role]);
                }
            } else {
                // Remove all roles if role_id is empty
                $user->syncRoles([]);
            }
        }

        // Load relationships for the response
        $user->load(['roles', 'loyaltyPoints', 'achievements', 'badges']);

        return $this->successItem(
            new UserResource($user),
            'User updated successfully.'
        );

    }

    public function destroy(string $id): JsonResponse
    {
        $this->authorize('delete', Auth::user());
        $user = User::find($id);
        if (! $user) {
            return $this->notFoundError('User', $id);
        }
        $user->tokens()->delete();
        $user->delete();

        return $this->successMessage('User deleted successfully.');
    }

    /**
     * Get all users' achievements and badge progress (Admin only)
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

            return $this->successCollection(
                $users,
                AdminUserResource::class,
                'Users achievements retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users data', 500, [
                $e->getMessage(),
            ]);
        }
    }

    /**
     * Get specific user's loyalty data (Admin only)
     * GET /api/admin/users/{user}/loyalty-data
     */
    public function getUserLoyaltyData(User $user): JsonResponse
    {
        try {
            $loyaltyData = $this->loyaltyService->getUserLoyaltyData($user);

            return $this->successItem(
                new LoyaltyDataResource($loyaltyData),
                'User loyalty data retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user loyalty data', 500, [
                $e->getMessage(),
            ]);
        }
    }
}
