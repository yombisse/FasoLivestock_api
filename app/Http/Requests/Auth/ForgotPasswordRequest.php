<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Autorisation
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
            'login' => 'required|string',
        ];
    }

    /**
     * Messages
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email ou téléphone requis.',
        ];
    }

    /**
     * JSON validation error
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