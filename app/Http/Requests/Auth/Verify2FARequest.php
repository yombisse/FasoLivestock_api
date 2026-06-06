<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Verify2FARequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_id' => 'required|uuid',
            'code' => 'required|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'verification_id.required' => 'verification_id requis.',
            'verification_id.uuid' => 'verification_id invalide.',
            'code.required' => 'code requis.',
            'code.digits' => 'Le code doit contenir 6 chiffres.',
        ];
    }

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

