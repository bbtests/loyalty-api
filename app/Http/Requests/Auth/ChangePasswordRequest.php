<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => 'required|string|min:8|different:new_password',
            'new_password' => 'required|string|min:8|same:new_password_confirmation',
            'new_password_confirmation' => 'required|string|min:8',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required',
            'current_password.min' => 'Current password must be at least 8 characters',
            'current_password.different' => 'Your new password should not be the same as your current password.',

            'new_password.required' => 'New password is required',
            'new_password.min' => 'New password must be at least 8 characters',
            'new_password.same' => 'New password and new password confirmation don\'t match',
            'new_password.different' => 'New password and current password cannot be the same',
        ];
    }
}
