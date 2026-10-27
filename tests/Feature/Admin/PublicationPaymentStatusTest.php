<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Models\ModelProfile;
use App\Models\User;
use App\Services\PublicationPaymentStatus;
use Livewire\Livewire;
use Tests\TestCase;

class PublicationPaymentStatusTest extends TestCase
{
    private function profile(array $periods): ModelProfile
    {
        $profile = User::factory()->withCompletedRegistration()->create()->modelProfile;
        foreach ($periods as [$start, $end]) {
            $profile->payments()->create([
                'registered_by' => $profile->user_id, 'paid_at' => today(), 'amount' => 50000,
                'period_days' => 30, 'starts_at' => today()->addDays($start), 'expires_at' => today()->addDays($end),
            ]);
        }

        return $profile;
    }

    public function test_status_and_filters_agree_for_renewals_expiry_boundaries_and_future_payments(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26)->startOfDay());
        config(['publication.expiry_warning_days' => 7]);
        $service = app(PublicationPaymentStatus::class);
        $cases = [
            ['unpaid', [], null],
            ['expired', [[-40, -10]], -10],
            ['current', [[-40, -10], [-10, 20]], 20],
            ['near_expiry', [[-30, 0]], 0],
            ['near_expiry', [[-20, 7]], 7],
            ['current', [[-20, 8]], 8],
            ['current', [[-30, 2], [-5, 25]], 25],
            ['scheduled', [[5, 35]], 35],
            ['expired', [[-40, -10], [5, 35]], -10],
            ['near_expiry', [[-30, 2], [10, 40]], 2],
        ];
        foreach ($cases as [$expected, $periods, $expiry]) {
            $profile = $this->profile($periods);
            $summary = $service->summary($profile);
            $this->assertSame($expected, $summary['status']);
            $this->assertSame($expiry === null ? null : today()->addDays($expiry)->toDateString(), $summary['expires_at']?->toDateString());
            foreach (array_keys(PublicationPaymentStatus::LABELS) as $status) {
                $this->assertSame($expected === $status, $service->filter(ModelProfile::whereKey($profile->id), $status)->exists());
            }
        }
    }

    public function test_filament_shows_renewed_payment_and_does_not_filter_it_as_expired(): void
    {
        $admin = User::factory()->withCompletedRegistration()->create(['is_admin' => true]);
        $renewed = $this->profile([[-40, -10], [-10, 20]]);
        $expired = $this->profile([[-40, -10]]);
        $near = $this->profile([[-20, 3]]);
        $this->actingAs($admin);

        Livewire::test(ListModelProfiles::class)
            ->assertCanSeeTableRecords([$renewed, $expired, $near])
            ->assertSee('Estado del pago')->assertSee('Vigente')->assertSee('Vencida')->assertSee('Próxima a vencer')
            ->filterTable('expired')->assertCanSeeTableRecords([$expired])->assertCanNotSeeTableRecords([$renewed, $near])
            ->resetTableFilters()->filterTable('near_expiry')->assertCanSeeTableRecords([$near])->assertCanNotSeeTableRecords([$renewed, $expired])
            ->resetTableFilters()->filterTable('payment_status', 'current')->assertCanSeeTableRecords([$renewed])->assertCanNotSeeTableRecords([$expired, $near]);

        $this->get(ModelProfileResource::getUrl('view', ['record' => $renewed]))
            ->assertOk()->assertSee('Vigente')->assertDontSee('Renovar pago vencido');
        $this->get(ModelProfileResource::getUrl('view', ['record' => $expired]))
            ->assertOk()->assertSee('Vencida')->assertSee('Renovar pago vencido');
    }
}
