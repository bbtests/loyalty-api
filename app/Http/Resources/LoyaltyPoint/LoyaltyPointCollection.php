<?php

namespace App\Http\Resources\LoyaltyPoint;

use Illuminate\Http\Resources\Json\ResourceCollection;

class LoyaltyPointCollection extends ResourceCollection
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
