<?php

declare(strict_types=1);

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

/**
 * @mixin \Spatie\Permission\Models\Role
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions */
        $permissions = $this->permissions;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $permissions->map(function (Permission $permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'created_at' => $permission->created_at,
                ];
            })->toArray(),
            'created_at' => $this->created_at,
        ];
    }
}
