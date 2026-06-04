<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed'        => 'Les deux mots de passe ne correspondent pas.',
            'password.min'              => 'Le nouveau mot de passe doit faire au moins 8 caractères.',
            'password.required'         => 'Veuillez saisir un nouveau mot de passe.',
            'current_password.required' => 'Votre mot de passe actuel est requis.',
        ];
    }
}
