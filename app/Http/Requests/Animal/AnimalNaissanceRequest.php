<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnimalNaissanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id'         => 'required|uuid|exists:farms,id',
            'nom'             => 'nullable|string|max:255',
            'sexe'            => 'required|in:male,femelle',
            'espece_id'       => 'nullable|uuid|exists:especes,id',
            'race'            => 'nullable|string|max:255',
            'date_naissance'  => 'required|date',
            'mother_id'       => 'nullable|uuid|exists:animals,id',
            'poids'           => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'farm_id.required'        => 'La ferme est obligatoire.',
            'farm_id.exists'          => 'La ferme sélectionnée est invalide.',
            'sexe.required'           => 'Le sexe est obligatoire.',
            'sexe.in'                 => 'Le sexe doit être : male ou femelle.',
            'espece_id.exists'        => 'L\'espèce sélectionnée est invalide.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'mother_id.exists'        => 'La mère sélectionnée est invalide.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Données invalides.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
