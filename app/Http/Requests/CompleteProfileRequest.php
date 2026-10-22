<?php

namespace App\Http\Requests;

use App\Validation\EnrollmentRules;
use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() === true;
    }

    public function rules(): array
    {
        return EnrollmentRules::rules($this->input('province_id')) + [
            'confirm_in_person_removal' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return EnrollmentRules::messages();
    }
}
