<?php

namespace App\Http\Requests\Mouvement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\TypeEvenement;

class UpdateMouvementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'animal_id'            => 'sometimes|uuid|exists:animals,id',
            'type_evenement_id'    => 'sometimes|uuid|exists:type_evenements,id',
            'date_evenement'       => 'sometimes|date',
            'description'          => 'sometimes|nullable|string',
            'cout'                 => 'sometimes|nullable|numeric|min:0',
            'farm_destination_id'  => 'sometimes|nullable|uuid|exists:farms,id',
            'transaction_id'       => 'sometimes|nullable|uuid|exists:transactions,id',
        ];

        // Validation conditionnelle pour TRANSFERT
        if ($this->type_evenement_id) {
            $typeEvenement = TypeEvenement::find($this->type_evenement_id);
            if ($typeEvenement && strtoupper($typeEvenement->nom_type) === 'TRANSFERT') {
                $rules['farm_destination_id'] = 'required|uuid|exists:farms,id';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'animal_id.exists'          => 'L\'animal sélectionné est invalide.',
            'type_evenement_id.exists'  => 'Le type d\'événement sélectionné est invalide.',
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
