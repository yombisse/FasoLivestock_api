<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\Animal;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id'              => 'required|string|min:16|max:20|exists:farms,id',
            'nom'                  => 'nullable|string|max:255',
            'race'                 => 'nullable|string|max:255',
            'sexe'                 => 'required|in:male,femelle',
            'poids'                => 'nullable|numeric|min:0',
            'espece_id'            => 'nullable|string|min:16|max:20|exists:especes,id',
            'lot_id'               => 'nullable|string|min:16|max:20|exists:lots,id',
            'numero_identification' => 'required|string|max:255|unique:animals,numero_identification',
            'photo'                => 'nullable|string|max:500',
            'statut'               => 'nullable|in:SAIN,MALADE,EN_TRAITEMENT,VENDU,MORT,PERDU',
            'origine'              => ['sometimes', 'string', Rule::in(Animal::ORIGINES)],
        ];
    }

    public function messages(): array
    {
        return [
            'farm_id.required'              => 'La ferme est obligatoire.',
            'farm_id.exists'                => 'La ferme sélectionnée est invalide.',
            'numero_identification.required' => 'Le numéro d\'identification est obligatoire.',
            'numero_identification.unique'  => 'Ce numéro d\'identification existe déjà.',
            'sexe.required'                 => 'Le sexe est obligatoire.',
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

    protected function prepareForValidation()
    {
        $this->merge([
            'statut' => 'SAIN',
        ]);
    }
}
