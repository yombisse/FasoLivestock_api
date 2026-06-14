<?php

namespace App\Http\Requests\Farm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|string|max:255',
            'location'    => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'users'       => 'sometimes|nullable|array',
            'users.*.id'  => 'required|uuid|exists:users,id',
            'users.*.role'=> 'required|string|in:owner,manager,vet,worker',
        ];
    }

    public function messages(): array
    {
        return [
            'users.*.id.exists' => 'Un utilisateur sélectionné est invalide.',
            'users.*.role.in'   => 'Le rôle doit être : owner, manager, vet ou worker.',
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