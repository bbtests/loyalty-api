<?php

namespace App\Http\Requests\LoyaltyPoint;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoyaltyPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit loyalty point');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'points' => 'sometimes|integer|min:0',
            'total_earned' => 'sometimes|integer|min:0',
            'total_redeemed' => 'sometimes|integer|min:0',
        ];
    }
}
