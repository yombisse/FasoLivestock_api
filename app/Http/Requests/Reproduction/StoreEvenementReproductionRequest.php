<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEvenementReproductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => 'required|uuid|exists:animals,id',
            'type_evenement_id' => 'required|uuid|exists:type_evenements,id',
            'date_evenement' => 'required|date',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',
            'farm_destination_id' => 'nullable|uuid|exists:farms,id',
            'statut_avant' => 'nullable|in:ACTIF,VENDU,MORT,PERDU',
            'statut_apres' => 'nullable|in:ACTIF,VENDU,MORT,PERDU',
            'transaction_id' => 'nullable|uuid|exists:transactions,id',
            'version' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'L\'animal est obligatoire.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'type_evenement_id.required' => 'Le type d\'événement est obligatoire.',
            'type_evenement_id.uuid' => 'L\'ID du type d\'événement doit être un UUID valide.',
            'type_evenement_id.exists' => 'Le type d\'événement spécifié n\'existe pas.',
            'date_evenement.required' => 'La date de l\'événement est obligatoire.',
            'date_evenement.date' => 'La date de l\'événement doit être une date valide.',
            'cout.numeric' => 'Le coût doit être un nombre.',
            'cout.min' => 'Le coût ne peut pas être négatif.',
            'farm_destination_id.uuid' => 'L\'ID de la ferme de destination doit être un UUID valide.',
            'farm_destination_id.exists' => 'La ferme de destination spécifiée n\'existe pas.',
            'statut_avant.in' => 'Le statut avant doit être ACTIF, VENDU, MORT ou PERDU.',
            'statut_apres.in' => 'Le statut après doit être ACTIF, VENDU, MORT ou PERDU.',
            'transaction_id.uuid' => 'L\'ID de la transaction doit être un UUID valide.',
            'transaction_id.exists' => 'La transaction spécifiée n\'existe pas.',
            'version.integer' => 'La version doit être un entier.',
            'version.min' => 'La version doit être au moins 1.',
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
