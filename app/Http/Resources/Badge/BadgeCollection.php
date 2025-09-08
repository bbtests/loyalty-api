<?php

namespace App\Http\Resources\Badge;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BadgeCollection extends ResourceCollection
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
