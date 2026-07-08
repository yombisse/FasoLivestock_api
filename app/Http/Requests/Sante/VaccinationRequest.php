<?php

namespace App\Http\Requests\Sante;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VaccinationRequest extends FormRequest
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

            // Champs spécifiques vaccination (dans metadonnees)
            'metadonnees.nom_vaccin' => 'required|string|max:255',
            'metadonnees.veterinaire' => 'nullable|string|max:255',
            'metadonnees.dosage' => 'nullable|string|max:100',
            'metadonnees.lot_vaccin' => 'nullable|string|max:100',
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
            'metadonnees.nom_vaccin.required' => 'Le nom du vaccin est obligatoire.',
            'metadonnees.nom_vaccin.string' => 'Le nom du vaccin doit être une chaîne de caractères.',
            'metadonnees.nom_vaccin.max' => 'Le nom du vaccin ne peut pas dépasser 255 caractères.',
            'metadonnees.veterinaire.string' => 'Le nom du vétérinaire doit être une chaîne de caractères.',
            'metadonnees.veterinaire.max' => 'Le nom du vétérinaire ne peut pas dépasser 255 caractères.',
            'metadonnees.dosage.string' => 'Le dosage doit être une chaîne de caractères.',
            'metadonnees.dosage.max' => 'Le dosage ne peut pas dépasser 100 caractères.',
            'metadonnees.lot_vaccin.string' => 'Le numéro de lot doit être une chaîne de caractères.',
            'metadonnees.lot_vaccin.max' => 'Le numéro de lot ne peut pas dépasser 100 caractères.',
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
