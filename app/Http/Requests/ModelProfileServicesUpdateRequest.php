<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\CompatibleServices;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModelProfileServicesUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user?->hasVerifiedEmail() === true && $user->modelProfile()->exists();
    }

    public function rules(): array
    {
        $profile = $this->user()?->modelProfile;

        return [
            'service_ids' => ['array', 'distinct'],
            'service_ids.*' => [
                'integer',
                Rule::exists('services', 'id')->where('is_active', true),
                ...($profile ? [new CompatibleServices($profile)] : []),
            ],
        ];
    }
}
