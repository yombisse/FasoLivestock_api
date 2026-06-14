<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:50|unique:roles,name',
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'required|string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Le nom du rôle est obligatoire.',
            'name.unique'          => 'Ce nom de rôle existe déjà.',
            'permissions.required' => 'Sélectionnez au moins une permission.',
            'permissions.min'      => 'Sélectionnez au moins une permission.',
            'permissions.*.exists' => 'Une permission sélectionnée est invalide.',
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