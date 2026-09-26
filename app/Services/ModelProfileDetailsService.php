<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ModelProfileDetailsService
{
    public function __construct(private readonly IdentityDocumentService $identityDocuments) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ModelProfile $profile, User $owner, array $data): ModelProfile
    {
        return DB::transaction(function () use ($owner, $data): ModelProfile {
            $lockedProfile = $owner->modelProfile()
                ->lockForUpdate()
                ->firstOrFail();

            $previousDetails = $lockedProfile->privateDetails;
            $identityDetailsChanged = $previousDetails !== null
                && (
                    $previousDetails->real_first_name !== $data['real_first_name']
                    || $previousDetails->real_last_name !== $data['real_last_name']
                    || $previousDetails->birth_date?->toDateString() !== $data['birth_date']
                );

            $lockedProfile->privateDetails()->updateOrCreate([], [
                'real_first_name' => $data['real_first_name'],
                'real_last_name' => $data['real_last_name'],
                'birth_date' => $data['birth_date'],
                'real_height_cm' => $data['real_height_cm'] ?? null,
                'real_weight_kg' => $data['real_weight_kg'] ?? null,
                'real_measurements' => $data['real_measurements'] ?? null,
                'private_phone' => $data['private_phone'] ?? null,
            ]);

            $lockedProfile->forceFill([
                'stage_name' => $data['stage_name'],
                'public_age' => $data['public_age'] ?? null,
                'show_age' => (bool) $data['show_age'],
                'nationality' => $data['nationality'],
                'availability_status' => $data['availability_status']
                    ?? $lockedProfile->availability_status
                    ?? 'available',
                'province_id' => array_key_exists('province_id', $data)
                    ? $data['province_id']
                    : $lockedProfile->province_id,
                'locality_id' => array_key_exists('locality_id', $data)
                    ? $data['locality_id']
                    : $lockedProfile->locality_id,
                'approximate_location_text' => array_key_exists('approximate_location_text', $data)
                    ? $data['approximate_location_text']
                    : $lockedProfile->approximate_location_text,
                'approximate_latitude' => array_key_exists('approximate_latitude', $data)
                    ? $data['approximate_latitude']
                    : $lockedProfile->approximate_latitude,
                'approximate_longitude' => array_key_exists('approximate_longitude', $data)
                    ? $data['approximate_longitude']
                    : $lockedProfile->approximate_longitude,
            ])->save();

            if ($identityDetailsChanged) {
                $this->identityDocuments->resetForProfileDataChange($lockedProfile);
            }

            return $lockedProfile->refresh();
        });
    }
}
