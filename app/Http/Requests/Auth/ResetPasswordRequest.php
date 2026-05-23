<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Autoriser la requête
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'login' => 'required|string', // email ou téléphone
            'token' => 'required|string',
            'password' => 'required|string|min:12|confirmed',
        ];
    }

    /**
     * Messages personnalisés
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email ou téléphone requis.',
            'token.required' => 'Token requis.',
            'password.required' => 'Mot de passe requis.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
        ];
    }

    /**
     * Réponse JSON propre (IMPORTANT pour API)
     */
    protected function failedValidation(Validator $validator)
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