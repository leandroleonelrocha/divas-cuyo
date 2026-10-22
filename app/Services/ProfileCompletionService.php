<?php

namespace App\Services;

use App\Models\Province;
use App\Models\PublicationType;
use App\Models\User;
use App\Validation\EnrollmentRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileCompletionService
{
    public function isComplete(User $user): bool
    {
        if (User::query()->whereKey($user->id)->value('profile_completion_status') === AccountEnrollmentState::INCOMPLETE) {
            return false;
        }

        // Read persisted values, rather than a cached relationship or a UI flag.
        $profile = $user->modelProfile()->first();
        if (! $profile) {
            return false;
        }

        $policies = $user->policyAcceptances()->whereNotNull('accepted_at')->pluck('policy');
        $data = [
            'name' => trim($profile->name ?? ''),
            'whatsapp' => trim($profile->whatsapp ?? ''),
            'province_id' => $profile->province_id,
            'locality_id' => $profile->locality_id,
            'publication_type_id' => $profile->publication_type_id,
            'terms_accepted' => $policies->contains('terms'),
            'privacy_accepted' => $policies->contains('privacy'),
        ];

        return Validator::make($data, EnrollmentRules::rules($profile->province_id, false))->passes();
    }

    public function complete(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $owner = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($owner->profile_completion_status === AccountEnrollmentState::PENDING || $this->isComplete($owner)) {
                return;
            }

            $province = Province::query()->active()->findOrFail($data['province_id']);
            $locality = ! empty($data['locality_id'])
                ? $province->localities()->active()->findOrFail($data['locality_id']) : null;
            $location = $locality?->name ?? $province->name;

            $owner->fill(['name' => $data['name'], 'whatsapp' => $data['whatsapp'], 'location' => $location])->save();
            $profile = $owner->modelProfile()->firstOrNew();
            $profile->fill([
                'name' => $data['name'], 'whatsapp' => $data['whatsapp'], 'location' => $location,
                'province_id' => $province->id, 'locality_id' => $locality?->id,
            ]);
            if (! $profile->exists) {
                $profile->fill(['is_published' => false, 'review_status' => 'pending']);
            }
            $profile->save();

            app(PublicationTypeChangeService::class)->change(
                $profile, PublicationType::query()->findOrFail($data['publication_type_id']),
                $owner, 'model', 'Modalidad elegida al completar la inscripción.',
                (bool) ($data['confirm_in_person_removal'] ?? false),
            );

            $owner->forceFill(['profile_completion_status' => AccountEnrollmentState::PENDING])->save();

            foreach (['terms', 'privacy'] as $policy) {
                $owner->policyAcceptances()->updateOrCreate(['policy' => $policy], [
                    'version' => config("policies.{$policy}_version"), 'accepted_at' => now(),
                ]);
            }
        }, 3);
    }
}
