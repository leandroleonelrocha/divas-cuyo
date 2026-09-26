<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicationTypeChangeRequest extends FormRequest
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
            'publication_type_id' => [
                'required',
                'integer',
                Rule::exists('publication_types', 'id')->where('is_active', true),
            ],
            'reason' => ['nullable', 'string', 'max:2000'],
            'confirm_in_person_removal' => ['sometimes', 'boolean'],
        ];
    }
}
