<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class IdentityDocumentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();
        $owner = $this->route('user');

        return $user?->is($owner)
            && $user->hasVerifiedEmail()
            && in_array($owner?->modelProfile?->identity_status, ['incomplete', 'rejected'], true);
    }
}
