<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create achievement');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:0',
            'badge_icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
