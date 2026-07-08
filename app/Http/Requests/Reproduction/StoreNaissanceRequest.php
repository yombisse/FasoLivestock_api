<?php

namespace App\Http\Requests\Reproduction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Animal;

class StoreNaissanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mother_id' => [
                'required',
                'uuid',
                'exists:animals,id',
                function ($attribute, $value, $fail) {
                    $animal = Animal::find($value);
                    if (!$animal) return;

                    if ($animal->sexe !== 'femelle') {
                        $fail('L\'animal sélectionné doit être une femelle.');
                        return;
                    }

                    if (!$animal->aGestationEnCours()) {
                        $fail(
                            'Cette femelle n\'a pas de gestation confirmée en cours. '
                            . 'Veuillez d\'abord enregistrer un événement '
                            . 'GESTATION_CONFIRMEE pour cet animal.'
                        );
                    }
                },
            ],
            'date_naissance' => 'required|date',
            'nombre_petits' => 'required|integer|min:0',
            'poids_naissance' => 'nullable|numeric|min:0',
            'observation' => 'nullable|string',
            'date_saillie' => 'nullable|date',
            'date_mise_bas_prevue' => 'nullable|date',
            'evenement_id' => 'nullable|uuid|exists:evenements,id',
            'creer_petits' => 'sometimes|boolean',
            'version' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'mother_id.required' => 'La mère est obligatoire.',
            'mother_id.uuid' => 'L\'ID de la mère doit être un UUID valide.',
            'mother_id.exists' => 'La mère spécifiée n\'existe pas.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'nombre_petits.required' => 'Le nombre de petits est obligatoire.',
            'nombre_petits.integer' => 'Le nombre de petits doit être un entier.',
            'nombre_petits.min' => 'Le nombre de petits ne peut pas être négatif.',
            'poids_naissance.numeric' => 'Le poids de naissance doit être un nombre.',
            'poids_naissance.min' => 'Le poids de naissance ne peut pas être négatif.',
            'date_saillie.date' => 'La date de saillie doit être une date valide.',
            'date_mise_bas_prevue.date' => 'La date de mise bas prévue doit être une date valide.',
            'evenement_id.uuid' => 'L\'ID de l\'événement doit être un UUID valide.',
            'evenement_id.exists' => 'L\'événement spécifié n\'existe pas.',
            'creer_petits.boolean' => 'Le champ créer_petits doit être un booléen.',
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
