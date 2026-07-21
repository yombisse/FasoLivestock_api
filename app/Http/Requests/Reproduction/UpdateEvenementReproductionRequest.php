<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEvenementReproductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => 'sometimes|string|min:16|max:20|exists:animals,id',
            'type_evenement_id' => 'sometimes|string|min:16|max:20|exists:type_evenements,id',
            'date_evenement' => 'sometimes|date',
            'description' => 'sometimes|nullable|string',
            'cout' => 'sometimes|nullable|numeric|min:0',
            'farm_destination_id' => 'sometimes|nullable|string|min:16|max:20|exists:farms,id',
            'statut_avant' => 'sometimes|nullable|in:SAIN,VENDU,MORT,PERDU',
            'statut_apres' => 'sometimes|nullable|in:SAIN,VENDU,MORT,PERDU',
            'transaction_id' => 'sometimes|nullable|string|min:16|max:20|exists:transactions,id',
            'version' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.string' => 'L\'ID de l\'animal doit être une chaîne de caractères.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'type_evenement_id.string' => 'L\'ID du type d\'événement doit être une chaîne de caractères.',
            'type_evenement_id.exists' => 'Le type d\'événement spécifié n\'existe pas.',
            'date_evenement.date' => 'La date de l\'événement doit être une date valide.',
            'cout.numeric' => 'Le coût doit être un nombre.',
            'cout.min' => 'Le coût ne peut pas être négatif.',
            'farm_destination_id.string' => 'L\'ID de la ferme de destination doit être une chaîne de caractères.',
            'farm_destination_id.exists' => 'La ferme de destination spécifiée n\'existe pas.',
            'statut_avant.in' => 'Le statut avant doit être SAIN, VENDU, MORT ou PERDU.',
            'statut_apres.in' => 'Le statut après doit être SAIN, VENDU, MORT ou PERDU.',
            'transaction_id.string' => 'L\'ID de la transaction doit être une chaîne de caractères.',
            'transaction_id.exists' => 'La transaction spécifiée n\'existe pas.',
            'version.required' => 'La version est obligatoire pour la mise à jour.',
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
