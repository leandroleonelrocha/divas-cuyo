<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'province_id' => ['required', 'integer', Rule::exists('provinces', 'id')->where('is_active', true)],
            'publication_type_id' => ['required', 'integer', Rule::exists('publication_types', 'id')
                ->where('is_active', true)
                ->whereIn('slug', array_keys(config('publication.monthly_prices_ars')))],
            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'province_id.required' => 'Elegí una provincia.',
            'province_id.integer' => 'La provincia seleccionada no es válida.',
            'province_id.exists' => 'La provincia seleccionada no está disponible.',
            'publication_type_id.required' => 'Elegí una modalidad de publicación.',
            'publication_type_id.exists' => 'La modalidad seleccionada no está disponible.',
            'terms_accepted.accepted' => 'Debés aceptar los términos y condiciones.',
            'privacy_accepted.accepted' => 'Debés aceptar la política de privacidad.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
