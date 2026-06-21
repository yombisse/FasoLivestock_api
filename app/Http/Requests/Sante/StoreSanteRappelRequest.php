<?php

namespace App\Http\Requests\Sante;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSanteRappelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => 'required|uuid|exists:animals,id',
            'type_rappel' => 'required|in:VACCINATION,TRAITEMENT,CONTROLE',
            'date_prevue' => 'required|date',
            'date_realisee' => 'nullable|date|after_or_equal:date_prevue',
            'statut' => 'nullable|in:EN_ATTENTE,REALISE,EN_RETARD',
            'note' => 'nullable|string',
            'evenement_id' => 'nullable|uuid|exists:evenements,id',
            'version' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'L\'animal est obligatoire.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'type_rappel.required' => 'Le type de rappel est obligatoire.',
            'type_rappel.in' => 'Le type de rappel doit être VACCINATION, TRAITEMENT ou CONTROLE.',
            'date_prevue.required' => 'La date prévue est obligatoire.',
            'date_prevue.date' => 'La date prévue doit être une date valide.',
            'date_realisee.date' => 'La date réalisée doit être une date valide.',
            'date_realisee.after_or_equal' => 'La date réalisée doit être postérieure ou égale à la date prévue.',
            'statut.in' => 'Le statut doit être EN_ATTENTE, REALISE ou EN_RETARD.',
            'note.string' => 'La note doit être une chaîne de caractères.',
            'evenement_id.uuid' => 'L\'ID de l\'événement doit être un UUID valide.',
            'evenement_id.exists' => 'L\'événement spécifié n\'existe pas.',
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
