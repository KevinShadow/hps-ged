<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $department = $this->route('department');

        $departmentId = is_object($department)
            ? $department->id
            : $department;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('departments', 'name')->ignore($departmentId),
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
}
