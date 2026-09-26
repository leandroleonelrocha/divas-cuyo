<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModelPhotoReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() === true
            && $this->user()->modelProfile()->exists();
    }

    public function rules(): array
    {
        return [
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo_ids.required' => 'No se pudo identificar el orden de tus fotografías.',
            'photo_ids.array' => 'El orden enviado no es válido.',
            'photo_ids.*.distinct' => 'No podés repetir una fotografía en el orden.',
        ];
    }
}
