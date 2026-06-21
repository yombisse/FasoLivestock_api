<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => 'required|string|max:100',
            'email'     => 'nullable|email|unique:users,email|required_without:telephone',
            'telephone' => 'nullable|string|max:20|unique:users,telephone|required_without:email',
            'photo'     => 'nullable|string|max:500',
            'password'  => 'required|string|min:12|confirmed',
            'roles'     => 'required|array|min:1',
            'roles.*'   => 'required|string|exists:roles,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Le nom est obligatoire.',
            'email.unique'       => 'Cet email est déjà utilisé.',
            'email.required_without' => 'Email ou téléphone est obligatoire.',
            'telephone.unique'   => 'Ce téléphone est déjà utilisé.',
            'telephone.required_without' => 'Email ou téléphone est obligatoire.',
            'password.min'       => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'roles.required'     => 'Assignez au moins un rôle.',
            'roles.*.exists'     => 'Un rôle sélectionné est invalide.',
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