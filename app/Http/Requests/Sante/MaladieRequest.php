<?php

namespace App\Http\Requests\Sante;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MaladieRequest extends FormRequest
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

            // Champs spécifiques maladie (dans metadonnees)
            'metadonnees.nom_maladie' => 'required|string|max:255',
            'metadonnees.symptomes' => 'nullable|string',
            'metadonnees.veterinaire' => 'nullable|string|max:255',
            'metadonnees.gravite' => 'nullable|in:legere,moderee,grave',
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
            'metadonnees.nom_maladie.required' => 'Le nom de la maladie est obligatoire.',
            'metadonnees.nom_maladie.string' => 'Le nom de la maladie doit être une chaîne de caractères.',
            'metadonnees.nom_maladie.max' => 'Le nom de la maladie ne peut pas dépasser 255 caractères.',
            'metadonnees.symptomes.string' => 'Les symptômes doivent être une chaîne de caractères.',
            'metadonnees.veterinaire.string' => 'Le nom du vétérinaire doit être une chaîne de caractères.',
            'metadonnees.veterinaire.max' => 'Le nom du vétérinaire ne peut pas dépasser 255 caractères.',
            'metadonnees.gravite.in' => 'La gravité doit être légère, modérée ou grave.',
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
