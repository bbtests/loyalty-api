<?php

namespace App\Http\Resources\CashbackPayment;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CashbackPaymentCollection extends ResourceCollection
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
