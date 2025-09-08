<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'points_earned' => $this->points_earned,
            'transaction_type' => $this->transaction_type,
            'external_transaction_id' => $this->external_transaction_id,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'metadata' => $this->metadata,
        ];
    }
}
