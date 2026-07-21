<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Champs communs
            'animal_id' => 'required|string|min:16|max:20|exists:animals,id',
            'date_evenement' => 'required|date',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',

            // Champs spécifiques gestation (dans metadonnees)
            'metadonnees.date_confirmation' => 'nullable|date',
            'metadonnees.methode_confirmation' => 'nullable|in:echographie,palpation,test_hormonal',
            'metadonnees.veterinaire' => 'nullable|string|max:255',
            'metadonnees.duree_gestation_estimee_jours' => 'nullable|integer|min:0',
            'metadonnees.date_mise_bas_prevue' => 'nullable|date',
            'metadonnees.nombre_petits_estime' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'L\'animal est obligatoire.',
            'animal_id.string' => 'L\'ID de l\'animal doit être une chaîne de caractères.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'date_evenement.required' => 'La date de l\'événement est obligatoire.',
            'date_evenement.date' => 'La date de l\'événement doit être une date valide.',
            'cout.numeric' => 'Le coût doit être un nombre.',
            'cout.min' => 'Le coût ne peut pas être négatif.',
            'metadonnees.date_confirmation.date' => 'La date de confirmation doit être une date valide.',
            'metadonnees.methode_confirmation.in' => 'La méthode de confirmation doit être échographie, palpation ou test hormonal.',
            'metadonnees.veterinaire.string' => 'Le nom du vétérinaire doit être une chaîne de caractères.',
            'metadonnees.veterinaire.max' => 'Le nom du vétérinaire ne peut pas dépasser 255 caractères.',
            'metadonnees.duree_gestation_estimee_jours.integer' => 'La durée de gestation estimée doit être un entier.',
            'metadonnees.duree_gestation_estimee_jours.min' => 'La durée de gestation estimée ne peut pas être négative.',
            'metadonnees.date_mise_bas_prevue.date' => 'La date de mise bas prévue doit être une date valide.',
            'metadonnees.nombre_petits_estime.integer' => 'Le nombre de petits estimé doit être un entier.',
            'metadonnees.nombre_petits_estime.min' => 'Le nombre de petits estimé ne peut pas être négatif.',
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
