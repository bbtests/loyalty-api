<?php

namespace App\Http\Requests\CashbackPayment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCashbackPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit cashback payment');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'transaction_id' => 'sometimes|exists:transactions,id',
            'amount' => 'sometimes|numeric|min:0',
            'payment_provider' => 'sometimes|string|max:100',
            'provider_transaction_id' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:50',
            'payment_details' => 'sometimes|json',
        ];
    }
}
