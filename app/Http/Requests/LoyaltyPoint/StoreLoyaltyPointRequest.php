<?php

namespace App\Http\Requests\LoyaltyPoint;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoyaltyPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create loyalty point');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:0',
            'total_earned' => 'required|integer|min:0',
            'total_redeemed' => 'required|integer|min:0',
        ];
    }
}
