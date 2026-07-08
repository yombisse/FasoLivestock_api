<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChaleurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Champs communs
            'animal_id' => 'required|uuid|exists:animals,id',
            'date_evenement' => 'required|date',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',

            // Champs spécifiques chaleur (dans metadonnees)
            'metadonnees.intensite' => 'nullable|in:faible,moyenne,forte',
            'metadonnees.duree_chaleur' => 'nullable|string|max:100',
            'metadonnees.comportement' => 'nullable|string',
            'metadonnees.date_prochaine_chaleur_estimee' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'L\'animal est obligatoire.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'date_evenement.required' => 'La date de l\'événement est obligatoire.',
            'date_evenement.date' => 'La date de l\'événement doit être une date valide.',
            'cout.numeric' => 'Le coût doit être un nombre.',
            'cout.min' => 'Le coût ne peut pas être négatif.',
            'metadonnees.intensite.in' => 'L\'intensité doit être faible, moyenne ou forte.',
            'metadonnees.duree_chaleur.string' => 'La durée de la chaleur doit être une chaîne de caractères.',
            'metadonnees.duree_chaleur.max' => 'La durée de la chaleur ne peut pas dépasser 100 caractères.',
            'metadonnees.comportement.string' => 'Le comportement doit être une chaîne de caractères.',
            'metadonnees.date_prochaine_chaleur_estimee.date' => 'La date de prochaine chaleur estimée doit être une date valide.',
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
