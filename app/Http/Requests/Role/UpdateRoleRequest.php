<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('id'); // Le paramètre de route est 'id' pour PUT/PATCH

        return [
            'name'          => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions'   => 'sometimes|array',
            'permissions.*' => 'required|string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'          => 'Ce nom de rôle existe déjà.',
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