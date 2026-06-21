<?php

namespace App\Http\Requests\Espece;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEspeceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'         => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.max' => 'Le nom de l\'espèce ne doit pas dépasser 255 caractères.',
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
