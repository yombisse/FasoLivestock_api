<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_transaction' => 'required|in:ENTREE,SORTIE,TRANSFERT,AJUSTEMENT',
            'montant' => 'required|numeric|min:0',
            'date_transaction' => 'required|date',
            'categorie_id' => 'nullable|uuid|exists:categories,id',
            'description' => 'nullable|string',
            'evenement_id' => 'nullable|uuid|exists:evenements,id',
            'version' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'type_transaction.required' => 'Le type de transaction est obligatoire.',
            'type_transaction.in' => 'Le type de transaction doit être ENTREE, SORTIE, TRANSFERT ou AJUSTEMENT.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant ne peut pas être négatif.',
            'date_transaction.required' => 'La date de transaction est obligatoire.',
            'date_transaction.date' => 'La date de transaction doit être une date valide.',
            'animal_id.uuid' => 'L\'ID de l\'animal doit être un UUID valide.',
            'animal_id.exists' => 'L\'animal spécifié n\'existe pas.',
            'categorie_id.uuid' => 'L\'ID de la catégorie doit être un UUID valide.',
            'categorie_id.exists' => 'La catégorie spécifiée n\'existe pas.',
            'evenement_id.uuid' => 'L\'ID de l\'événement doit être un UUID valide.',
            'evenement_id.exists' => 'L\'événement spécifié n\'existe pas.',
            'description.string' => 'La description doit être une chaîne de caractères.',
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
