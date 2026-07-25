<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id'                         => ['required', 'string', 'min:16', 'max:20', 'exists:farms,id'],
            'animaux'                          => ['required', 'array', 'min:1', 'max:500'],
            'animaux.*.espece_id'             => ['required', 'string', 'min:16', 'max:20', 'exists:especes,id'],
            'animaux.*.sexe'                  => ['required', Rule::in(['male', 'femelle'])],
            'animaux.*.nom'                   => ['nullable', 'string', 'max:100'],
            'animaux.*.race'                  => ['nullable', 'string', 'max:100'],
            'animaux.*.date_naissance'        => ['nullable', 'date', 'before_or_equal:today'],
            'animaux.*.poids'                 => ['nullable', 'numeric', 'min:0'],
            'animaux.*.numero_identification' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'animaux.required'          => 'Aucun animal à importer.',
            'animaux.min'               => 'Ajoutez au moins un animal.',
            'animaux.max'               => '500 animaux maximum par import.',
            'animaux.*.espece_id.required' => 'L\'espèce est obligatoire (ligne :index).',
            'animaux.*.sexe.required'   => 'Le sexe est obligatoire (ligne :index).',
            'animaux.*.sexe.in'         => 'Le sexe doit être male ou femelle (ligne :index).',
        ];
    }
}
