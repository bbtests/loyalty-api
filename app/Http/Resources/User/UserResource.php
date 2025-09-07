<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles */
        $roles = $this->roles;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles->map(function (Role $role) {
                /** @var \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions */
                $permissions = $role->permissions;

                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $permissions->map(function (Permission $permission) {
                        return $permission->name;
                    })->toArray(),
                ];
            })->toArray(),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
