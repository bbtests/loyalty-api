<?php

namespace App\Http\Requests\CashbackPayment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashbackPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create cashback payment');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'transaction_id' => 'nullable|exists:transactions,id',
            'amount' => 'required|numeric|min:0',
            'payment_provider' => 'required|string|max:100',
            'provider_transaction_id' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'payment_details' => 'nullable|json',
        ];
    }
}
