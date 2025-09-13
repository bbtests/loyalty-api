<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'external_transaction_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'User not found',
            'amount.required' => 'Transaction amount is required',
            'amount.min' => 'Transaction amount must be at least ₦0.01',
            'amount.max' => 'Transaction amount cannot exceed ₦999,999.99',
        ];
    }
}
