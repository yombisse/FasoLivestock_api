<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MiseBasRequest extends FormRequest
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

            // Champs spécifiques mise bas (dans metadonnees)
            'metadonnees.heure_mise_bas' => 'nullable|date_format:H:i',
            'metadonnees.duree_mise_bas' => 'nullable|string|max:100',
            'metadonnees.nombre_petits' => 'nullable|integer|min:0',
            'metadonnees.nombre_males' => 'nullable|integer|min:0',
            'metadonnees.nombre_femelles' => 'nullable|integer|min:0',
            'metadonnees.nombre_morts_ne' => 'nullable|integer|min:0',
            'metadonnees.poids_moyen_petits_kg' => 'nullable|numeric|min:0',
            'metadonnees.assistance_veterinaire' => 'nullable|boolean',
            'metadonnees.veterinaire' => 'nullable|string|max:255',
            'metadonnees.complications' => 'nullable|string',
            'metadonnees.note' => 'nullable|string',
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
            'metadonnees.heure_mise_bas.date_format' => 'L\'heure de mise bas doit être au format HH:MM.',
            'metadonnees.duree_mise_bas.string' => 'La durée de la mise bas doit être une chaîne de caractères.',
            'metadonnees.duree_mise_bas.max' => 'La durée de la mise bas ne peut pas dépasser 100 caractères.',
            'metadonnees.nombre_petits.integer' => 'Le nombre de petits doit être un entier.',
            'metadonnees.nombre_petits.min' => 'Le nombre de petits ne peut pas être négatif.',
            'metadonnees.nombre_males.integer' => 'Le nombre de mâles doit être un entier.',
            'metadonnees.nombre_males.min' => 'Le nombre de mâles ne peut pas être négatif.',
            'metadonnees.nombre_femelles.integer' => 'Le nombre de femelles doit être un entier.',
            'metadonnees.nombre_femelles.min' => 'Le nombre de femelles ne peut pas être négatif.',
            'metadonnees.nombre_morts_ne.integer' => 'Le nombre de morts-nés doit être un entier.',
            'metadonnees.nombre_morts_ne.min' => 'Le nombre de morts-nés ne peut pas être négatif.',
            'metadonnees.poids_moyen_petits_kg.numeric' => 'Le poids moyen des petits doit être un nombre.',
            'metadonnees.poids_moyen_petits_kg.min' => 'Le poids moyen des petits ne peut pas être négatif.',
            'metadonnees.assistance_veterinaire.boolean' => 'L\'assistance vétérinaire doit être un booléen.',
            'metadonnees.veterinaire.string' => 'Le nom du vétérinaire doit être une chaîne de caractères.',
            'metadonnees.veterinaire.max' => 'Le nom du vétérinaire ne peut pas dépasser 255 caractères.',
            'metadonnees.complications.string' => 'Les complications doivent être une chaîne de caractères.',
            'metadonnees.note.string' => 'La note doit être une chaîne de caractères.',
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
