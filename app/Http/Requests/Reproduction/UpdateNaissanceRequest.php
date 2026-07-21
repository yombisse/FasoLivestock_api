<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateNaissanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mother_id' => 'sometimes|string|min:16|max:20|exists:animals,id',
            'date_naissance' => 'sometimes|date',
            'nombre_petits' => 'sometimes|integer|min:0',
            'poids_naissance' => 'sometimes|nullable|numeric|min:0',
            'observation' => 'sometimes|nullable|string',
            'date_saillie' => 'sometimes|nullable|date',
            'date_mise_bas_prevue' => 'sometimes|nullable|date',
            'evenement_id' => 'sometimes|nullable|string|min:16|max:20|exists:evenements,id',
            'version' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'mother_id.string' => 'L\'ID de la mère doit être une chaîne de caractères.',
            'mother_id.exists' => 'La mère spécifiée n\'existe pas.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'nombre_petits.integer' => 'Le nombre de petits doit être un entier.',
            'nombre_petits.min' => 'Le nombre de petits ne peut pas être négatif.',
            'poids_naissance.numeric' => 'Le poids de naissance doit être un nombre.',
            'poids_naissance.min' => 'Le poids de naissance ne peut pas être négatif.',
            'date_saillie.date' => 'La date de saillie doit être une date valide.',
            'date_mise_bas_prevue.date' => 'La date de mise bas prévue doit être une date valide.',
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
