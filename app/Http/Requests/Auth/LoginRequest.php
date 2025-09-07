<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ];
    }

    /**
     * Get the body parameters for API documentation.
     *
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'The user\'s email address',
                'example' => 'john.doe@example.com',
            ],
            'password' => [
                'description' => 'The user\'s password',
                'example' => 'password123',
            ],
            'remember' => [
                'description' => 'Whether to remember the user session (optional)',
                'example' => true,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'password.required' => 'Password is required',
        ];
    }
}
