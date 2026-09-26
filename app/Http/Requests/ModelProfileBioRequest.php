<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ModelProfileBioRequest extends FormRequest
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
            'content' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
