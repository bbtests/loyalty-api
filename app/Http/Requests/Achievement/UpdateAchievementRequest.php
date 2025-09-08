<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit achievement');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'points_required' => 'sometimes|integer|min:0',
            'badge_icon' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
