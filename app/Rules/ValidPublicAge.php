<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPublicAge implements ValidationRule
{
    public function __construct(private readonly ?string $birthDate) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! $this->birthDate) {
            $fail('Necesitás indicar tu fecha de nacimiento antes de definir una edad pública.');

            return;
        }

        $realAge = Carbon::parse($this->birthDate)->age;
        $publicAge = (int) $value;

        if ($publicAge > $realAge || $publicAge < $realAge - 5) {
            $fail('La edad pública debe estar entre tu edad real y hasta cinco años menos.');
        }
    }
}
