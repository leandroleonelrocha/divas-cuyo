<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\LocalityBelongsToProvince;
use App\Rules\ValidPublicAge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountProfileUpdateRequest extends FormRequest
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
            'real_first_name' => ['required', 'string', 'max:100'],
            'real_last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'real_height_cm' => ['nullable', 'integer', 'between:80,250'],
            'real_weight_kg' => ['nullable', 'numeric', 'between:20,400'],
            'real_measurements' => ['nullable', 'string', 'max:100'],
            'private_phone' => ['nullable', 'string', 'max:50'],
            'slug' => ['prohibited'],
            'stage_name' => ['required', 'string', 'max:100'],
            'public_age' => ['nullable', 'integer', 'between:18,120', new ValidPublicAge($this->input('birth_date'))],
            'show_age' => ['required', 'boolean'],
            'nationality' => ['required', 'string', 'max:100'],
            // Optional for backwards-compatible clients that predate US2; the
            // database default keeps legacy profiles in the available state.
            'availability_status' => ['sometimes', 'in:available,unavailable'],
            'province_id' => [
                'sometimes',
                'nullable',
                Rule::exists('provinces', 'id')->where('is_active', true),
            ],
            'locality_id' => [
                'sometimes',
                'nullable',
                'exists:localities,id',
                new LocalityBelongsToProvince($this->input('province_id')),
            ],
            'approximate_location_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'approximate_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'approximate_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
