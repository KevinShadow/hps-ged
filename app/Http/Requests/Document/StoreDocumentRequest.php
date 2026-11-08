<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return [
            'monthly_submission_id' => [
                'required',
                'integer',
                'exists:monthly_submissions,id',
            ],

            'files' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],
            'files.*' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx',
                'max:10240',
            ],
            'relative_paths' => [
                'sometimes',
                'array',
                'max:20',
            ],
            'relative_paths.*' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Veuillez sélectionner au moins un document ou un dossier.',
            'files.array' => 'La sélection des documents est invalide.',
            'files.min' => 'Veuillez sélectionner au moins un document.',
            'files.max' => 'Une requête ne peut contenir plus de 20 documents.',
            'files.*.required' => 'Chaque élément sélectionné doit être un fichier.',
            'files.*.file' => 'Un des éléments sélectionnés est invalide.',
            'files.*.mimes' => 'Formats autorisés : PDF, DOC, DOCX, XLS et XLSX.',
            'files.*.max' => 'Chaque document ne doit pas dépasser 10 Mo.',
            'relative_paths.*.max' => 'Le chemin d’un sous-dossier ne peut pas dépasser 255 caractères.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $relativePaths = $this->input('relative_paths', []);

                if (! is_array($relativePaths)) {
                    return;
                }

                foreach ($relativePaths as $index => $relativePath) {
                    if (! is_string($relativePath)) {
                        continue;
                    }

                    $segments = array_filter(
                        explode('/', str_replace('\\', '/', trim($relativePath))),
                        fn (string $segment): bool => $segment !== ''
                    );

                    if (
                        $segments === []
                        || in_array('.', $segments, true)
                        || in_array('..', $segments, true)
                    ) {
                        $validator->errors()->add(
                            "relative_paths.{$index}",
                            'Le chemin du sous-dossier sélectionné est invalide.'
                        );
                    }
                }
            },
        ];
    }
}
