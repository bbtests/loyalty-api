<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<int, UserResource>|\Illuminate\Contracts\Support\Arrayable<int, UserResource>|\JsonSerializable
     */
    public function toArray($request)
    {
        return UserResource::collection($this->collection);
    }
}
