<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group User Management
 *
 * APIs for managing users, roles, permissions
 */
class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {

            $pagination = $this::applyPagination($request);

            $users = User::orderBy($pagination['sort_by'], $pagination['sort_order'])
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

            // Create user
            $user = User::create($validated);

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

            $user = User::find($id);

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
        $user->update($validated);

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
}
