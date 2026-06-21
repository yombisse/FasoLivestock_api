<?php

namespace App\Http\Requests\Alimentation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAlimentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'unite' => 'required|in:KG,LITRE,SAC,BOTTE',
            'prix_unitaire' => 'nullable|numeric|min:0',
            'stock_actuel' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'version' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de l\'aliment est obligatoire.',
            'nom.string' => 'Le nom de l\'aliment doit être une chaîne de caractères.',
            'nom.max' => 'Le nom de l\'aliment ne peut pas dépasser 255 caractères.',
            'unite.required' => 'L\'unité est obligatoire.',
            'unite.in' => 'L\'unité doit être KG, LITRE, SAC ou BOTTE.',
            'prix_unitaire.numeric' => 'Le prix unitaire doit être un nombre.',
            'prix_unitaire.min' => 'Le prix unitaire ne peut pas être négatif.',
            'stock_actuel.numeric' => 'Le stock actuel doit être un nombre.',
            'stock_actuel.min' => 'Le stock actuel ne peut pas être négatif.',
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
