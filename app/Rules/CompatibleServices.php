<?php

namespace App\Rules;

use App\Models\ModelProfile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class CompatibleServices implements ValidationRule
{
    public function __construct(private readonly ModelProfile $profile) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $publicationType = $this->profile->publicationType;
        if (! $publicationType) {
            $fail('Seleccioná un tipo de publicación antes de elegir servicios.');

            return;
        }

        $service = DB::table('services')->where('id', $value)->first();
        if (! $service || ! $service->is_active) {
            $fail('El servicio seleccionado no está disponible.');

            return;
        }

        if ($publicationType->slug === 'virtual' && $service->service_type !== 'virtual') {
            $fail('Solo Virtual no permite servicios presenciales.');
        }
    }
}
