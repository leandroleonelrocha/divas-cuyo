<?php

namespace App\Services;

use App\Models\ModelProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProfileStatistics
{
    public const TIMEZONE = 'America/Argentina/Buenos_Aires';

    public function record(ModelProfile $profile, string $visitor, string $event): void
    {
        if (! in_array($event, ['visit', 'whatsapp'], true)) {
            throw new \InvalidArgumentException('Evento inválido.');
        }
        DB::transaction(function () use ($profile, $visitor, $event) {
            // Serialize updates for the same profile, including simultaneous first events.
            ModelProfile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $key = ['model_profile_id' => $profile->id, 'visitor_hash' => $visitor, 'event' => $event];
            $last = DB::table('profile_stat_visitors')->where($key)->value('counted_at');
            if ($last && CarbonImmutable::parse($last)->gt(now()->subMinutes(30))) {
                return;
            }
            DB::table('profile_stat_visitors')->updateOrInsert($key, ['counted_at' => now()]);
            $day = ['model_profile_id' => $profile->id, 'day' => CarbonImmutable::now(self::TIMEZONE)->toDateString()];
            DB::table('profile_stat_days')->insertOrIgnore($day);
            DB::table('profile_stat_days')->where($day)->increment($event === 'visit' ? 'visits' : 'whatsapp_clicks');
        }, 3);
    }

    public function summary(ModelProfile $profile, string $period = '30'): array
    {
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $period = in_array($period, ['7', '30', 'month'], true) ? $period : '30';
        $start = $period === 'month' ? $today->startOfMonth() : $today->subDays((int) $period - 1);
        $records = DB::table('profile_stat_days')->where('model_profile_id', $profile->id)
            ->whereBetween('day', [$start->toDateString(), $today->toDateString()])->get()->keyBy('day');
        $days = [];
        for ($day = $start; $day->lte($today); $day = $day->addDay()) {
            $row = $records->get($day->toDateString());
            $days[] = ['date' => $day->format('d/m'), 'visits' => (int) ($row->visits ?? 0), 'clicks' => (int) ($row->whatsapp_clicks ?? 0)];
        }
        $visits = array_sum(array_column($days, 'visits'));
        $clicks = array_sum(array_column($days, 'clicks'));

        return [
            'period' => $period, 'days' => $days, 'visits' => $visits, 'clicks' => $clicks,
            'rate' => $visits ? round($clicks / $visits * 100, 1) : null,
            'max' => max(1, ...array_column($days, 'visits'), ...array_column($days, 'clicks')),
            'range' => $start->format('d/m/Y').' — '.$today->format('d/m/Y'),
        ];
    }
}
