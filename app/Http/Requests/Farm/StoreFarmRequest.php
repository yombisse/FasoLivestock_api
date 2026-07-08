<?php

namespace App\Http\Requests\Farm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'location'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'type_elevage'     => 'nullable|in:bovin,ovin,caprin,porcin,volaille,cunicole,mixte,autre',
            'photo'            => 'nullable|string|max:500', // URL de l'image (upload fait côté web admin)
            'owner_id'         => 'nullable|uuid|exists:users,id',
            'from_admin_panel' => 'nullable|boolean', // Flag pour indiquer que la requête vient de l'admin panel
            'users'            => 'nullable|array',
            'users.*.id'       => 'required|uuid|exists:users,id',
            'users.*.role'     => 'required|string|in:owner,manager,vet,worker',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Le nom de la ferme est obligatoire.',
            'users.*.id.exists'   => 'Un utilisateur sélectionné est invalide.',
            'users.*.role.in'     => 'Le rôle doit être : owner, manager, vet ou worker.',
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