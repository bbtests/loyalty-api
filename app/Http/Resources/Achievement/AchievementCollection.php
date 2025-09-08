<?php

namespace App\Http\Resources\Achievement;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AchievementCollection extends ResourceCollection
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
