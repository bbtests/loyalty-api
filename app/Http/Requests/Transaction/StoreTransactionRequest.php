<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create transaction');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'points_earned' => 'required|integer|min:0',
            'transaction_type' => 'required|string|max:50',
            'external_transaction_id' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'metadata' => 'nullable|json',
        ];
    }
}
