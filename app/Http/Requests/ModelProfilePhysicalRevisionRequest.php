<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ModelProfilePhysicalRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user?->hasVerifiedEmail() === true && $user->modelProfile()->exists();
    }

    public function rules(): array
    {
        return [
            'height_cm' => ['required', 'integer', 'between:80,250'],
            'weight_kg' => ['required', 'numeric', 'between:20,400'],
            'measurements' => ['required', 'string', 'max:100'],
            'eye_color' => ['required', 'string', 'max:50'],
            'hair_color' => ['nullable', 'string', 'max:50'],
            'skin_color' => ['nullable', 'string', 'max:50'],
            'body_type' => ['nullable', 'string', 'max:50'],
            'nationality' => ['required', 'string', 'max:100'],
        ];
    }
}
