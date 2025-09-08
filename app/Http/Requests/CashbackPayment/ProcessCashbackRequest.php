<?php

namespace App\Http\Requests\CashbackPayment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCashbackRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0.01|max:10000',
            'transaction_id' => 'nullable|integer|exists:transactions,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Cashback amount is required',
            'amount.min' => 'Minimum cashback amount is $0.01',
            'amount.max' => 'Maximum cashback amount is $10,000',
            'transaction_id.exists' => 'Transaction not found',
        ];
    }
}
