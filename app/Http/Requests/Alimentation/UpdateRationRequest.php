<?php

namespace App\Http\Requests\Alimentation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aliment_id' => 'sometimes|uuid|exists:aliments,id',
            'animal_id' => 'sometimes|nullable|uuid|exists:animals,id',
            'lot_id' => 'sometimes|nullable|uuid|exists:lots,id',
            'quantite' => 'sometimes|numeric|min:0',
            'date_distribution' => 'sometimes|date',
            'heure_distribution' => 'sometimes|nullable|date_format:H:i',
            'observation' => 'sometimes|nullable|string',
            'version' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'aliment_id.uuid' => 'L\'ID de l\'aliment doit être un UUID valide.',
            'aliment_id.exists' => 'L\'aliment spécifié n\'existe pas.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'lot_id.uuid' => 'L\'ID du lot doit être un UUID valide.',
            'lot_id.exists' => 'Le lot spécifié n\'existe pas.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'quantite.min' => 'La quantité ne peut pas être négative.',
            'date_distribution.date' => 'La date de distribution doit être une date valide.',
            'heure_distribution.date_format' => 'L\'heure de distribution doit être au format HH:MM.',
            'observation.string' => 'L\'observation doit être une chaîne de caractères.',
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
