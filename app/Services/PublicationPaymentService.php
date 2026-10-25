<?php

namespace App\Services;

use App\Models\ModelProfile;
use App\Models\PublicationPayment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class PublicationPaymentService
{
    public function register(ModelProfile $profile, User $admin, array $data): PublicationPayment
    {
        return DB::transaction(function () use ($profile, $admin, $data): PublicationPayment {
            $startsAt = isset($data['starts_at']) ? today()->parse($data['starts_at']) : today();
            $periodDays = (int) $data['period_days'];

            if ($periodDays < 1 || (float) $data['amount'] <= 0) {
                throw new LogicException('El importe y el período deben ser mayores que cero.');
            }

            return $profile->payments()->create([
                'registered_by' => $admin->getKey(),
                'paid_at' => $data['paid_at'] ?? today(),
                'amount' => $data['amount'],
                'period_days' => $periodDays,
                'starts_at' => $startsAt,
                'expires_at' => $startsAt->copy()->addDays($periodDays),
                'internal_note' => $data['internal_note'] ?? null,
            ]);
        });
    }

    public function hasCurrentPayment(ModelProfile $profile, ?CarbonInterface $date = null): bool
    {
        $date ??= today();

        return $profile->payments()
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('expires_at', '>=', $date)
            ->exists();
    }

    public function canPublish(ModelProfile $profile): bool
    {
        return $profile->identity_status === 'approved'
            && $profile->review_status === 'approved'
            && $this->hasCurrentPayment($profile);
    }
}
