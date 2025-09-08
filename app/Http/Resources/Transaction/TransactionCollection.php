<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TransactionCollection extends ResourceCollection
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
