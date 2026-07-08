<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $animalId = $this->route('animal');

        return [
            'farm_id'              => 'sometimes|uuid|exists:farms,id',
            'nom'                  => 'sometimes|nullable|string|max:255',
            'race'                 => 'sometimes|nullable|string|max:255',
            'sexe'                 => 'sometimes|in:male,femelle',
            'date_naissance'       => 'sometimes|nullable|date',
            'poids'                => 'sometimes|nullable|numeric|min:0',
            'espece_id'            => 'sometimes|nullable|uuid|exists:especes,id',
            'lot_id'               => 'sometimes|nullable|uuid|exists:lots,id',
            'mother_id'            => 'sometimes|nullable|uuid|exists:animals,id',
            'numero_identification' => 'sometimes|string|max:255|unique:animals,numero_identification,' . $animalId,
            'photo'                => 'sometimes|nullable|string|max:500',
            'naissance_id'         => 'sometimes|nullable|uuid|exists:naissances,id',
        ];
    }

    public function messages(): array
    {
        return [
            'farm_id.exists'                => 'La ferme sélectionnée est invalide.',
            'numero_identification.unique'  => 'Ce numéro d\'identification existe déjà.',
            'sexe.in'                       => 'Le sexe doit être : male ou femelle.',
            'espece_id.exists'              => 'L\'espèce sélectionnée est invalide.',
            'lot_id.exists'                 => 'Le lot sélectionné est invalide.',
            'mother_id.exists'              => 'La mère sélectionnée est invalide.',
            'naissance_id.exists'           => 'La naissance sélectionnée est invalide.',
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
