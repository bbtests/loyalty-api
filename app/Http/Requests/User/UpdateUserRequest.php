<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    use ApiResponseTrait;

    public function authorize(): bool
    {
        return $this->user()?->can('edit user');
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$this->route('user'),
            'password' => 'sometimes|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = [];
        foreach ($validator->errors()->toArray() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'field' => $field,
                    'message' => $message,
                ];
            }
        }

        throw new HttpResponseException(
            $this->errorResponse(
                'Validation failed',
                422,
                $errors
            )
        );
    }
}
