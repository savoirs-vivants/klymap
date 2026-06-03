<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:100'],
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => 'Le prénom est obligatoire.',
            'firstname.max'      => 'Le prénom ne peut pas dépasser 100 caractères.',
            'name.required'      => 'Le nom est obligatoire.',
            'name.max'           => 'Le nom ne peut pas dépasser 100 caractères.',
            'email.required'     => 'L\'adresse email est obligatoire.',
            'email.email'        => 'L\'adresse email n\'est pas valide.',
            'email.unique'       => 'Cette adresse email est déjà utilisée.',
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }
}
