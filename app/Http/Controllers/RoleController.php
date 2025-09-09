<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\Role\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * @group Role Management
 *
 * APIs for managing roles
 */
class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $pagination = $this::applyPagination($request);

            $roles = Role::with('permissions')
                ->orderBy($pagination['sort_by'], $pagination['sort_order'])
                ->paginate($pagination['per_page']);

            return $this->successCollection(
                $roles,
                RoleResource::class,
                'Roles fetched successfully.',
                $this->buildFilters([
                    'search_query' => $request->input('search'),
                ])
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve roles', 422, [
                $e->getMessage(),
            ]);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $role = Role::with('permissions')->find($id);

            if (! $role) {
                return $this->notFoundError('Role', $id);
            }

            return $this->successItem(
                new RoleResource($role),
                'Role retrieved successfully.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve role', 422, [
                $e->getMessage(),
            ]);
        }
    }
}
