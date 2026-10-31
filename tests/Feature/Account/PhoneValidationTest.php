<?php

namespace Tests\Feature\Account;

use App\Http\Requests\AccountProfileUpdateRequest;
use App\Validation\EnrollmentRules;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    public function test_phone_fields_accept_only_digits(): void
    {
        $request = new AccountProfileUpdateRequest;
        $rules = [
            'whatsapp' => EnrollmentRules::rules(null)['whatsapp'],
            'private_phone' => $request->rules()['private_phone'],
        ];

        foreach ($rules as $field => $rule) {
            foreach (['5492611234567', '0012345'] as $valid) {
                $this->assertTrue(Validator::make([$field => $valid], [$field => $rule])->passes());
            }
            foreach (['abc', '+5492611234567', '261 1234567', '261-1234567', "123\n", '1.5', '1e5'] as $invalid) {
                $this->assertTrue(Validator::make([$field => $invalid], [$field => $rule])->fails());
            }
        }
        $this->assertTrue(Validator::make(['private_phone' => null], ['private_phone' => $rules['private_phone']])->passes());
    }
}
