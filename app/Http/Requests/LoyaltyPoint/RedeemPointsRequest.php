<?php

namespace App\Http\Requests\LoyaltyPoint;

use Illuminate\Foundation\Http\FormRequest;

class RedeemPointsRequest extends FormRequest
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
            'points' => 'required|integer|min:1|max:1000000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'points.required' => 'Points amount is required',
            'points.integer' => 'Points must be a whole number',
            'points.min' => 'Minimum redemption is 1 point',
            'points.max' => 'Maximum redemption is 1,000,000 points',
        ];
    }
}
