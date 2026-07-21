<?php

namespace App\Http\Requests\Sante;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSanteRappelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => 'sometimes|string|min:16|max:20|exists:animals,id',
            'type_rappel' => 'sometimes|in:VACCINATION,TRAITEMENT,CONTROLE',
            'date_prevue' => 'sometimes|date',
            'date_realisee' => 'sometimes|nullable|date|after_or_equal:date_prevue',
            'statut' => 'sometimes|in:EN_ATTENTE,REALISE,EN_RETARD',
            'note' => 'sometimes|nullable|string',
            'evenement_id' => 'sometimes|nullable|string|min:16|max:20|exists:evenements,id',
            'version' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.string' => 'L\'ID de l\'animal doit être une chaîne de caractères.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'type_rappel.in' => 'Le type de rappel doit être VACCINATION, TRAITEMENT ou CONTROLE.',
            'date_prevue.date' => 'La date prévue doit être une date valide.',
            'date_realisee.date' => 'La date réalisée doit être une date valide.',
            'date_realisee.after_or_equal' => 'La date réalisée doit être postérieure ou égale à la date prévue.',
            'statut.in' => 'Le statut doit être EN_ATTENTE, REALISE ou EN_RETARD.',
            'note.string' => 'La note doit être une chaîne de caractères.',
            'evenement_id.string' => 'L\'ID de l\'événement doit être une chaîne de caractères.',
            'evenement_id.exists' => 'L\'événement spécifié n\'existe pas.',
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
