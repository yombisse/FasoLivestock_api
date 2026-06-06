<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    /**
     * Autoriser inscription
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation
     */
    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            /**
             * EMAIL OU TELEPHONE
             * MAIS PAS LES DEUX
             */

            'email' => [
                'nullable',
                'email',
                'unique:users,email',
                'required_without:telephone',
                'prohibited_if:telephone,',
            ],

            'telephone' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,telephone',
                'required_without:email',
                'prohibited_if:email,',
            ],

            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ];
    }

    /**
     * Messages personnalisés
     */
    public function messages(): array
    {
        return [

            'name.required' =>
                'Le nom est obligatoire.',

            /**
             * EMAIL
             */
            'email.required_without' =>
                'L’email ou le téléphone est obligatoire.',

            'email.prohibited_with' =>
                'Vous ne pouvez pas fournir email et téléphone simultanément.',

            'email.email' =>
                'Format d’email invalide.',

            'email.unique' =>
                'Cet email est déjà utilisé.',

            /**
             * TELEPHONE
             */
            'telephone.required_without' =>
                'Le téléphone ou l’email est obligatoire.',

            'telephone.prohibited_with' =>
                'Vous ne pouvez pas fournir téléphone et email simultanément.',

            'telephone.unique' =>
                'Ce numéro est déjà utilisé.',

            /**
             * PASSWORD
             */
            'password.required' =>
                'Le mot de passe est obligatoire.',

            'password.min' =>
                'Le mot de passe doit contenir au moins 12 caractères.',

            'password.confirmed' =>
                'Les mots de passe ne correspondent pas.',
        ];
    }

    /**
     * JSON response
     */
    protected function failedValidation($validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}