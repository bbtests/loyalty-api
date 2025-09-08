<?php

namespace App\Http\Requests\Badge;

use Illuminate\Foundation\Http\FormRequest;

class StoreBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create badge');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'required|json',
            'icon' => 'nullable|string|max:255',
            'tier' => 'integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
