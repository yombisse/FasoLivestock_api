<?php

namespace App\Http\Requests\Alimentation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aliment_id' => 'required|uuid|exists:aliments,id',
            'animal_id' => 'nullable|uuid|exists:animals,id|required_without:lot_id',
            'lot_id' => 'nullable|uuid|exists:lots,id|required_without:animal_id',
            'quantite' => 'required|numeric|min:0',
            'date_distribution' => 'required|date',
            'heure_distribution' => 'nullable|date_format:H:i',
            'observation' => 'nullable|string',
            'version' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'aliment_id.required' => 'L\'aliment est obligatoire.',
            'aliment_id.uuid' => 'L\'ID de l\'aliment doit être un UUID valide.',
            'aliment_id.exists' => 'L\'aliment spécifié n\'existe pas.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'animal_id.required_without' => 'L\'animal est obligatoire si aucun lot n\'est spécifié.',
            'lot_id.uuid' => 'L\'ID du lot doit être un UUID valide.',
            'lot_id.exists' => 'Le lot spécifié n\'existe pas.',
            'lot_id.required_without' => 'Le lot est obligatoire si aucun animal n\'est spécifié.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'quantite.min' => 'La quantité ne peut pas être négative.',
            'date_distribution.required' => 'La date de distribution est obligatoire.',
            'date_distribution.date' => 'La date de distribution doit être une date valide.',
            'heure_distribution.date_format' => 'L\'heure de distribution doit être au format HH:MM.',
            'observation.string' => 'L\'observation doit être une chaîne de caractères.',
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
