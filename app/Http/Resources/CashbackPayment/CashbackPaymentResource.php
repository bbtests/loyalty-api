<?php

namespace App\Http\Resources\CashbackPayment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CashbackPayment
 */
class CashbackPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'payment_provider' => $this->payment_provider,
            'provider_transaction_id' => $this->provider_transaction_id,
            'status' => $this->status,
            'payment_details' => $this->payment_details,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
