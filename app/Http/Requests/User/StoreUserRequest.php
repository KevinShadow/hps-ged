<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],

            'department_id' => [
                'required',
                'integer',
                'exists:departments,id',
            ],

            'role' => [
                'required',
                Rule::enum(UserRole::class),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
