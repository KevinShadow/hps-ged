<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
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
                'max:100',
                'unique:departments,name',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du service est obligatoire.',
            'name.unique' => 'Ce service existe déjà.',
            'name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
        ];
    }
}
