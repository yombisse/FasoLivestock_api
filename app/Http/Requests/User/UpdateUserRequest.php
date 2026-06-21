<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name'      => 'sometimes|string|max:100',
            'email'     => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'telephone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'telephone')->ignore($userId),
            ],
            'photo'     => 'sometimes|nullable|string|max:500',
            'password'  => 'sometimes|nullable|string|min:12|confirmed',
            'roles'     => 'sometimes|array|min:1',
            'roles.*'   => 'required|string|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'Cet email est déjà utilisé.',
            'telephone.unique' => 'Ce téléphone est déjà utilisé.',
            'password.min'     => 'Le mot de passe doit contenir au moins 12 caractères.',
            'roles.*.exists'   => 'Un rôle sélectionné est invalide.',
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