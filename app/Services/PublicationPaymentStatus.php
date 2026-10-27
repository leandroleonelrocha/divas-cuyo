<?php

namespace App\Services;

use App\Models\ModelProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class PublicationPaymentStatus
{
    public const LABELS = [
        'current' => 'Vigente',
        'near_expiry' => 'Próxima a vencer',
        'expired' => 'Vencida',
        'scheduled' => 'Programada',
        'unpaid' => 'Sin pago',
    ];

    public static function color(string $status): string
    {
        return match ($status) {
            'current' => 'success',
            'near_expiry' => 'warning',
            'expired' => 'danger',
            'scheduled' => 'info',
            default => 'gray',
        };
    }

    /** Dates are inclusive, matching PublicationPaymentService::hasCurrentPayment. */
    public function summary(ModelProfile $profile, ?CarbonInterface $date = null): array
    {
        $today = ($date ?? today())->copy()->startOfDay();
        $limit = $today->copy()->addDays(max(0, (int) config('publication.expiry_warning_days', 7)));
        $profile->loadMissing('payments');
        $current = $profile->payments->filter(fn ($payment) => $payment->starts_at->lte($today) && $payment->expires_at->gte($today))
            ->sortByDesc('expires_at')->first();
        if ($current) {
            return ['status' => $current->expires_at->lte($limit) ? 'near_expiry' : 'current', 'expires_at' => $current->expires_at];
        }
        $expired = $profile->payments->filter(fn ($payment) => $payment->expires_at->lt($today))->sortByDesc('expires_at')->first();
        if ($expired) {
            return ['status' => 'expired', 'expires_at' => $expired->expires_at];
        }
        $future = $profile->payments->filter(fn ($payment) => $payment->starts_at->gt($today))->sortBy('starts_at')->first();

        return ['status' => $future ? 'scheduled' : 'unpaid', 'expires_at' => $future?->expires_at];
    }

    public function filter(Builder $query, string $status): Builder
    {
        $today = today();
        $limit = $today->copy()->addDays(max(0, (int) config('publication.expiry_warning_days', 7)));
        $current = fn (Builder $payment): Builder => $payment->whereDate('starts_at', '<=', $today)->whereDate('expires_at', '>=', $today);
        $longCurrent = fn (Builder $payment): Builder => $current($payment)->whereDate('expires_at', '>', $limit);

        return match ($status) {
            'current' => $query->whereHas('payments', $longCurrent),
            'near_expiry' => $query->whereHas('payments', fn (Builder $payment): Builder => $current($payment)->whereDate('expires_at', '<=', $limit))
                ->whereDoesntHave('payments', $longCurrent),
            'expired' => $query->whereHas('payments', fn (Builder $payment): Builder => $payment->whereDate('expires_at', '<', $today))
                ->whereDoesntHave('payments', $current),
            'scheduled' => $query->whereHas('payments', fn (Builder $payment): Builder => $payment->whereDate('starts_at', '>', $today))
                ->whereDoesntHave('payments', fn (Builder $payment): Builder => $payment->whereDate('expires_at', '<', $today))
                ->whereDoesntHave('payments', $current),
            'unpaid' => $query->whereDoesntHave('payments'),
            default => $query,
        };
    }
}
