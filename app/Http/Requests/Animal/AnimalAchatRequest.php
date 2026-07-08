<?php

namespace App\Http\Requests\Animal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnimalAchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farm_id'               => 'required|uuid|exists:farms,id',
            'farm_source_id'        => 'nullable|uuid|exists:farms,id',
            'nom'                   => 'nullable|string|max:255',
            'race'                  => 'nullable|string|max:255',
            'sexe'                  => 'required|in:male,femelle',
            'espece_id'             => 'nullable|uuid|exists:especes,id',
            'lot_id'                => 'nullable|uuid|exists:lots,id',
            'numero_identification' => 'required|string|max:255|unique:animals,numero_identification',
            'poids'                 => 'nullable|numeric|min:0',
            'provenance'            => 'nullable|string|max:255|required_without:farm_source_id',
            'prix_achat'            => 'required|numeric|min:0',
            'date_achat'            => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'farm_id.required'               => 'La ferme est obligatoire.',
            'farm_id.exists'                 => 'La ferme sélectionnée est invalide.',
            'farm_source_id.exists'          => 'La ferme source sélectionnée est invalide.',
            'numero_identification.required' => 'Le numéro d\'identification est obligatoire.',
            'numero_identification.unique'  => 'Ce numéro d\'identification existe déjà.',
            'sexe.required'                  => 'Le sexe est obligatoire.',
            'sexe.in'                        => 'Le sexe doit être : male ou femelle.',
            'espece_id.exists'               => 'L\'espèce sélectionnée est invalide.',
            'lot_id.exists'                  => 'Le lot sélectionné est invalide.',
            'provenance.required_without'    => 'La provenance est obligatoire si aucune ferme source n\'est sélectionnée.',
            'prix_achat.required'            => 'Le prix d\'achat est obligatoire.',
            'prix_achat.numeric'             => 'Le prix d\'achat doit être un nombre.',
            'prix_achat.min'                 => 'Le prix d\'achat doit être positif.',
            'date_achat.required'            => 'La date d\'achat est obligatoire.',
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
