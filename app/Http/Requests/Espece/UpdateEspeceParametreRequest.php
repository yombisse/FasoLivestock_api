<?php

namespace App\Http\Requests\Espece;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEspeceParametreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duree_gestation_jours'    => 'sometimes|nullable|integer|min:1',
            'age_reproduction_mois'    => 'sometimes|nullable|integer|min:1',
            'nombre_petits_typique'    => 'sometimes|nullable|integer|min:1',
            'intervalle_vaccin_jours'  => 'sometimes|nullable|integer|min:1',
            'age_sevrage_jours'        => 'sometimes|nullable|integer|min:1',
            'poids_naissance_moyen_kg' => 'sometimes|nullable|numeric|min:0',
            'poids_adulte_moyen_kg'    => 'sometimes|nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'duree_gestation_jours.integer'    => 'La durée de gestation doit être un entier.',
            'duree_gestation_jours.min'         => 'La durée de gestation doit être au moins 1 jour.',
            'age_reproduction_mois.integer'    => 'L\'âge de reproduction doit être un entier.',
            'age_reproduction_mois.min'         => 'L\'âge de reproduction doit être au moins 1 mois.',
            'nombre_petits_typique.integer'    => 'Le nombre de petits typique doit être un entier.',
            'nombre_petits_typique.min'         => 'Le nombre de petits typique doit être au moins 1.',
            'intervalle_vaccin_jours.integer'  => 'L\'intervalle de vaccin doit être un entier.',
            'intervalle_vaccin_jours.min'       => 'L\'intervalle de vaccin doit être au moins 1 jour.',
            'age_sevrage_jours.integer'        => 'L\'âge de sevrage doit être un entier.',
            'age_sevrage_jours.min'             => 'L\'âge de sevrage doit être au moins 1 jour.',
            'poids_naissance_moyen_kg.numeric' => 'Le poids de naissance moyen doit être un nombre.',
            'poids_naissance_moyen_kg.min'      => 'Le poids de naissance moyen doit être positif.',
            'poids_adulte_moyen_kg.numeric'    => 'Le poids adulte moyen doit être un nombre.',
            'poids_adulte_moyen_kg.min'          => 'Le poids adulte moyen doit être positif.',
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
