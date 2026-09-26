<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class LocalityBelongsToProvince implements ValidationRule
{
    public function __construct(private readonly mixed $provinceId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->provinceId === null || $this->provinceId === '') {
            $fail('Seleccioná una provincia antes de elegir la localidad.');

            return;
        }

        $valid = DB::table('localities')
            ->join('provinces', 'provinces.id', '=', 'localities.province_id')
            ->where('localities.id', $value)
            ->where('localities.province_id', $this->provinceId)
            ->where('localities.is_active', true)
            ->where('provinces.is_active', true)
            ->exists();

        if (! $valid) {
            $fail('La localidad seleccionada no pertenece a la provincia indicada.');
        }
    }
}
