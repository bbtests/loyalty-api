<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit transaction');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'sometimes|numeric|min:0',
            'points_earned' => 'sometimes|integer|min:0',
            'transaction_type' => 'sometimes|string|max:50',
            'external_transaction_id' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:50',
            'metadata' => 'sometimes|json',
        ];
    }
}
