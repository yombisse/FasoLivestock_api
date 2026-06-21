<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id'              => 'required|uuid|exists:farms,id',
            'nom'                  => 'required|string|max:255',
            'race'                 => 'nullable|string|max:255',
            'sexe'                 => 'required|in:male,femelle',
            'date_naissance'       => 'nullable|date',
            'poids'                => 'nullable|numeric|min:0',
            'espece_id'            => 'nullable|uuid|exists:especes,id',
            'lot_id'               => 'nullable|uuid|exists:lots,id',
            'mother_id'            => 'nullable|uuid|exists:animals,id',
            'statut'               => 'nullable|string|max:50',
            'numero_identification' => 'nullable|string|max:255',
            'photo'                => 'nullable|string|max:500',
            'naissance_id'         => 'nullable|uuid|exists:naissances,id',
        ];
    }

    public function messages(): array
    {
        return [
            'farm_id.required'     => 'La ferme est obligatoire.',
            'farm_id.exists'       => 'La ferme sélectionnée est invalide.',
            'nom.required'         => 'Le nom de l\'animal est obligatoire.',
            'sexe.required'        => 'Le sexe est obligatoire.',
            'sexe.in'              => 'Le sexe doit être : male ou femelle.',
            'espece_id.exists'     => 'L\'espèce sélectionnée est invalide.',
            'lot_id.exists'        => 'Le lot sélectionné est invalide.',
            'mother_id.exists'     => 'La mère sélectionnée est invalide.',
            'naissance_id.exists'  => 'La naissance sélectionnée est invalide.',
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
