<?php

declare(strict_types=1);

namespace App\Http\Resources\Role;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RoleCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<int, RoleResource>|\Illuminate\Contracts\Support\Arrayable<int, RoleResource>|\JsonSerializable
     */
    public function toArray($request)
    {
        return RoleResource::collection($this->collection);
    }
}
