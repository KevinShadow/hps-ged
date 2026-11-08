<?php

namespace App\Http\Requests\Submission;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active
            && $this->user()?->isEmployee();
    }

    public function rules(): array
    {
        return [
            'year' => [
                'required',
                'integer',
                'min:2020',
                'max:2100',
            ],

            'month' => [
                'required',
                'integer',
                'between:1,12',
            ],
        ];
    }
}
