<?php

namespace App\Http\Requests\Lot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssignAnimalsToLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_ids' => 'required|array|min:1',
            'animal_ids.*' => 'required|uuid|exists:animals,id',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_ids.required' => 'La liste des animaux est obligatoire.',
            'animal_ids.array' => 'La liste des animaux doit être un tableau.',
            'animal_ids.min' => 'Au moins un animal doit être fourni.',
            'animal_ids.*.required' => 'Chaque ID d\'animal est obligatoire.',
            'animal_ids.*.uuid' => 'Chaque ID d\'animal doit être un UUID valide.',
            'animal_ids.*.exists' => 'Un ou plusieurs animaux n\'existent pas.',
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
