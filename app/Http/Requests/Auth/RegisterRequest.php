<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    /**
     * Autoriser l'inscription
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation des données
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'required|string|max:20|unique:users,telephone',
            'password' => 'required|string|min:6|confirmed',
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',

            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'Format d’email invalide.',
            'email.unique' => 'Cet email est déjà utilisé.',

            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.unique' => 'Ce numéro est déjà utilisé.',

            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }


    public function failedValidation( $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}