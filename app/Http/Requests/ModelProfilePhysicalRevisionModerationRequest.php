<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ModelProfilePhysicalRevisionModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user instanceof User && $user->isVerifiedAdmin();
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
