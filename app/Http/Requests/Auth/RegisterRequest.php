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

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:255'],
            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_accepted.accepted' => 'Debés aceptar los términos y condiciones.',
            'privacy_accepted.accepted' => 'Debés aceptar la política de privacidad.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
