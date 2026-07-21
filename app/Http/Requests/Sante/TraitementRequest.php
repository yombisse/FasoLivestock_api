<?php

namespace App\Http\Requests\Sante;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TraitementRequest extends FormRequest
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

            // Champs spécifiques traitement (dans metadonnees)
            'metadonnees.nom_medicament' => 'required|string|max:255',
            'metadonnees.veterinaire' => 'nullable|string|max:255',
            'metadonnees.dosage' => 'nullable|string|max:100',
            'metadonnees.duree' => 'nullable|string|max:100',
            'metadonnees.frequence' => 'nullable|string|max:100',
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
            'metadonnees.nom_medicament.required' => 'Le nom du médicament est obligatoire.',
            'metadonnees.nom_medicament.string' => 'Le nom du médicament doit être une chaîne de caractères.',
            'metadonnees.nom_medicament.max' => 'Le nom du médicament ne peut pas dépasser 255 caractères.',
            'metadonnees.veterinaire.string' => 'Le nom du vétérinaire doit être une chaîne de caractères.',
            'metadonnees.veterinaire.max' => 'Le nom du vétérinaire ne peut pas dépasser 255 caractères.',
            'metadonnees.dosage.string' => 'Le dosage doit être une chaîne de caractères.',
            'metadonnees.dosage.max' => 'Le dosage ne peut pas dépasser 100 caractères.',
            'metadonnees.duree.string' => 'La durée doit être une chaîne de caractères.',
            'metadonnees.duree.max' => 'La durée ne peut pas dépasser 100 caractères.',
            'metadonnees.frequence.string' => 'La fréquence doit être une chaîne de caractères.',
            'metadonnees.frequence.max' => 'La fréquence ne peut pas dépasser 100 caractères.',
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
