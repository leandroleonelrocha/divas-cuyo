<?php

namespace App\Validation;

use App\Rules\LocalityBelongsToProvince;
use Illuminate\Validation\Rule;

class EnrollmentRules
{
    public static function rules(mixed $provinceId, bool $selecting = true): array
    {
        $province = Rule::exists('provinces', 'id');
        $locality = Rule::exists('localities', 'id');
        $type = Rule::exists('publication_types', 'id');

        // Existing selections remain complete when a catalog entry is retired.
        if ($selecting) {
            $province->where('is_active', true);
            $locality->where('is_active', true);
            $type->where('is_active', true)->whereIn('slug', array_keys(config('publication.monthly_prices_ars')));
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:50', ...($selecting ? ['regex:/^[0-9]+$/D'] : [])],
            'province_id' => ['required', 'integer', $province],
            'locality_id' => ['nullable', 'integer', $locality, new LocalityBelongsToProvince($provinceId, $selecting)],
            'publication_type_id' => ['required', 'integer', $type],
            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'Ingresá tu nombre público.',
            'whatsapp.required' => 'Ingresá tu WhatsApp.',
            'whatsapp.regex' => 'El WhatsApp debe contener solo números, sin espacios, signos ni letras.',
            'province_id.required' => 'Elegí una provincia.',
            'province_id.exists' => 'La provincia seleccionada no está disponible.',
            'locality_id.exists' => 'La ciudad seleccionada no está disponible.',
            'publication_type_id.required' => 'Elegí una modalidad de publicación.',
            'publication_type_id.exists' => 'La modalidad seleccionada no está disponible.',
            'terms_accepted.accepted' => 'Debés aceptar los términos y condiciones.',
            'privacy_accepted.accepted' => 'Debés aceptar la política de privacidad.',
        ];
    }
}
