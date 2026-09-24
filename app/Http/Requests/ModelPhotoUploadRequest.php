<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModelPhotoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() === true
            && $this->user()->modelProfile()->exists();
    }

    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'max:'.config('model-photos.max_size_kb'),
                'mimetypes:'.implode(',', config('model-photos.allowed_mimes')),
                'mimes:'.implode(',', config('model-photos.allowed_extensions')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Seleccioná una fotografía para cargar.',
            'photo.max' => 'La fotografía no puede superar los 10 MB.',
            'photo.mimetypes' => 'Sólo se permiten imágenes JPEG, PNG o WebP válidas.',
            'photo.mimes' => 'Sólo se permiten imágenes JPEG, PNG o WebP válidas.',
        ];
    }
}
