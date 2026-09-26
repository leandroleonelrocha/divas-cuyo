<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIdentityDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $mime = $value?->getMimeType();

        if (! in_array($mime, config('identity-documents.allowed_mimes'), true)) {
            $fail(__('validation.identity_document.mime', [], 'es'));
        }

        if ($value?->getSize() > config('identity-documents.max_size_kb') * 1024) {
            $fail(__('validation.identity_document.size', [], 'es'));
        }
    }
}
