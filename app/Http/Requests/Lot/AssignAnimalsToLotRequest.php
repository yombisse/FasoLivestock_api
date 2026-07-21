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
            'animal_ids.*' => 'required|string|min:16|max:20|exists:animals,id',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_ids.required' => 'La liste des animaux est obligatoire.',
            'animal_ids.array' => 'La liste des animaux doit être un tableau.',
            'animal_ids.min' => 'Au moins un animal doit être fourni.',
            'animal_ids.*.required' => 'Chaque ID d\'animal est obligatoire.',
            'animal_ids.*.string' => 'Chaque ID d\'animal doit être une chaîne de caractères.',
            'animal_ids.*.min' => 'Chaque ID d\'animal doit faire au moins 16 caractères.',
            'animal_ids.*.max' => 'Chaque ID d\'animal ne peut pas dépasser 20 caractères.',
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
