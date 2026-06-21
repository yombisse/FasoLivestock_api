<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_transaction' => 'sometimes|in:ENTREE,SORTIE,TRANSFERT,AJUSTEMENT',
            'montant' => 'sometimes|numeric|min:0',
            'date_transaction' => 'sometimes|date',
            'animal_id' => 'sometimes|nullable|uuid|exists:animals,id',
            'categorie_id' => 'sometimes|nullable|uuid|exists:categories,id',
            'description' => 'sometimes|nullable|string',
            'evenement_id' => 'sometimes|nullable|uuid|exists:evenements,id',
            'version' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'type_transaction.in' => 'Le type de transaction doit être ENTREE, SORTIE, TRANSFERT ou AJUSTEMENT.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant ne peut pas être négatif.',
            'date_transaction.date' => 'La date de transaction doit être une date valide.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'categorie_id.uuid' => 'L\'ID de la catégorie doit être un UUID valide.',
            'categorie_id.exists' => 'La catégorie spécifiée n\'existe pas.',
            'evenement_id.uuid' => 'L\'ID de l\'événement doit être un UUID valide.',
            'evenement_id.exists' => 'L\'événement spécifié n\'existe pas.',
            'description.string' => 'La description doit être une chaîne de caractères.',
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
