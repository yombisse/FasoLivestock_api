<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaillieRequest extends FormRequest
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

            // Champs spécifiques saillie (dans metadonnees)
            'metadonnees.male_id' => 'required|uuid|exists:animals,id',
            'metadonnees.male_nom' => 'nullable|string|max:255',
            'metadonnees.type_saillie' => 'required|in:naturelle,insemination_artificielle',
            'metadonnees.veterinaire' => 'nullable|string|max:255',
            'metadonnees.success' => 'nullable|boolean',
            'metadonnees.nombre_tentatives' => 'nullable|integer|min:1',
            'metadonnees.note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'L\'animal (mère) est obligatoire.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'date_evenement.required' => 'La date de l\'événement est obligatoire.',
            'date_evenement.date' => 'La date de l\'événement doit être uma date valide.',
            'cout.numeric' => 'Le coût doit être un nombre.',
            'cout.min' => 'Le coût ne peut pas être négatif.',
            'metadonnees.male_id.required' => 'Le mâle (père) est obligatoire pour une saillie.',
            'metadonnees.male_id.uuid' => 'L\'ID du mâle doit être un UUID valide.',
            'metadonnees.male_id.exists' => 'Le mâle spécifié n\'existe pas.',
            'metadonnees.male_nom.string' => 'Le nom du mâle doit être une chaîne de caractères.',
            'metadonnees.male_nom.max' => 'Le nom du mâle ne peut pas dépasser 255 caractères.',
            'metadonnees.type_saillie.required' => 'Le type de saillie est obligatoire.',
            'metadonnees.type_saillie.in' => 'Le type de saillie doit être naturelle ou insémination artificielle.',
            'metadonnees.veterinaire.string' => 'Le nom du vétérinaire doit être une chaîne de caractères.',
            'metadonnees.veterinaire.max' => 'Le nom du vétérinaire ne peut pas dépasser 255 caractères.',
            'metadonnees.success.boolean' => 'Le succès doit être un booléen.',
            'metadonnees.nombre_tentatives.integer' => 'Le nombre de tentatives doit être un entier.',
            'metadonnees.nombre_tentatives.min' => 'Le nombre de tentatives doit être au moins 1.',
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
