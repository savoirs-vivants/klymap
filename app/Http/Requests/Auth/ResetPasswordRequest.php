<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'     => 'L\'adresse email est obligatoire.',
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }
}
