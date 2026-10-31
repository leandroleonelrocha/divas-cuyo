<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Models\PaymentInstruction;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class ManagementDashboardTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_visible_profile_shows_management_then_expiry_action(): void
    {
        $profile = $this->publicProfile();
        $user = $profile->user;
        $profile->update(['publication_type_id' => \App\Models\PublicationType::factory()->create()->id]);
        foreach (['terms', 'privacy'] as $policy) {
            $user->policyAcceptances()->create(['policy' => $policy, 'version' => config("policies.{$policy}_version"), 'accepted_at' => now()]);
        }
        $bio = $profile->bios()->create(['status' => 'approved', 'content' => 'Biografía aprobada']);
        $profile->update(['current_bio_id' => $bio->id]);
        $payment = $profile->payments()->create([
            'registered_by' => $user->id, 'paid_at' => today(), 'amount' => 50000,
            'period_days' => 30, 'starts_at' => today(), 'expires_at' => today()->addDays(30),
        ]);
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk()
            ->assertSee('Vigencia activa')->assertSee('Ver mi perfil público')
            ->assertSee('Mis servicios')->assertSee('Mi identidad')
            ->assertDontSee('Tu camino para publicar');
        $payment->update(['expires_at' => today()->addDay()]);
        $this->get(route('account.dashboard'))->assertOk()
            ->assertSee('Próxima a vencer')->assertSee('La renovación no es automática.');
        $payment->update(['starts_at' => today()->subDays(30), 'expires_at' => today()->subDay()]);
        $this->get(route('account.dashboard'))->assertOk()
            ->assertSee('Tu publicación está vencida')->assertSee('Ver cómo renovar')
            ->assertDontSee('Ver mi perfil público');
    }

    public function test_approved_content_shows_management_and_payment_without_publishing(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $profile = $user->modelProfile;
        $profile->forceFill(['identity_status' => 'approved', 'review_status' => 'approved', 'is_published' => false])->save();
        $this->publicPhoto($profile, 0, true);
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk()->assertSee('Prepará tu publicación');
        $bio = $profile->bios()->create(['status' => 'approved', 'content' => 'Biografía aprobada']);
        $profile->update(['current_bio_id' => $bio->id]);
        PaymentInstruction::query()->create(['alias' => 'divas.pago', 'is_active' => true]);
        $this->get(route('account.dashboard'))->assertOk()
            ->assertSee('Tu perfil está aprobado')->assertSee('Mis datos')
            ->assertDontSee('Historial de pagos')->assertDontSee('divas.pago')->assertSee('Sin pago')
            ->assertSee('Tu publicación no está visible')
            ->assertDontSee('Prepará tu publicación')->assertDontSee('de 2 pasos');
        $this->get(route('account.dashboard', ['section' => 'payments']))->assertOk()
            ->assertSee('Historial de pagos')->assertSee('divas.pago');
        $profile->bios()->create(['status' => 'pending', 'content' => 'Cambio pendiente']);
        $this->get(route('account.dashboard'))->assertOk()->assertSee('Tu perfil está aprobado')
            ->assertSee('Cambios en revisión')->assertDontSee('Prepará tu publicación');
        $this->assertFalse($profile->fresh()->is_published);
    }
}
