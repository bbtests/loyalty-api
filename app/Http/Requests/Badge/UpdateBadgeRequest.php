<?php

namespace App\Http\Requests\Badge;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit badge');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'requirements' => 'sometimes|json',
            'icon' => 'sometimes|string|max:255',
            'tier' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
