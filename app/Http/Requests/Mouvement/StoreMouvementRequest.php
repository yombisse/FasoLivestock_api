<?php

namespace App\Http\Requests\Mouvement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\TypeEvenement;

class StoreMouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'animal_id'            => 'required|uuid|exists:animals,id',
            'type_evenement_id'    => 'required|uuid|exists:type_evenements,id',
            'date_evenement'       => 'required|date',
            'description'          => 'nullable|string',
            'cout'                 => 'nullable|numeric|min:0',
            'farm_destination_id'  => 'nullable|uuid|exists:farms,id',
            'transaction_id'       => 'nullable|uuid|exists:transactions,id',
        ];

        // Validation conditionnelle pour TRANSFERT
        $typeEvenement = TypeEvenement::find($this->type_evenement_id);
        if ($typeEvenement && strtoupper($typeEvenement->nom_type) === 'TRANSFERT') {
            $rules['farm_destination_id'] = 'required|uuid|exists:farms,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'animal_id.required'        => 'L\'animal est obligatoire.',
            'animal_id.exists'          => 'L\'animal sélectionné est invalide.',
            'type_evenement_id.required' => 'Le type d\'événement est obligatoire.',
            'type_evenement_id.exists'  => 'Le type d\'événement sélectionné est invalide.',
            'date_evenement.required'   => 'La date de l\'événement est obligatoire.',
            'farm_destination_id.required' => 'La ferme de destination est obligatoire pour un transfert.',
            'farm_destination_id.exists' => 'La ferme de destination est invalide.',
            'transaction_id.exists'     => 'La transaction sélectionnée est invalide.',
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
