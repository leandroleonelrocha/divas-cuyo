<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidIdentityDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdentityDocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();
        $owner = $this->route('user');

        return $user?->is($owner)
            && $user->hasVerifiedEmail();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(config('identity-documents.required_types'))],
            'document' => ['required', 'file', new ValidIdentityDocument],
        ];
    }
}
