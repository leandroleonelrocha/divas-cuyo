<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Tests\TestCase;

class PublicationSetupDashboardTest extends TestCase
{
    public function test_content_stage_requires_both_approvals_and_shows_existing_content(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $profile = $user->modelProfile;
        $this->actingAs($user);
        foreach ([['pending', 'approved'], ['approved', 'pending']] as [$identity, $review]) {
            $profile->forceFill(['identity_status' => $identity, 'review_status' => $review])->save();
            $this->get(route('account.dashboard'))->assertOk()
                ->assertSee('Completá tu perfil')->assertDontSee('Prepará tu publicación');
        }
        $profile->forceFill(['identity_status' => 'approved', 'review_status' => 'approved'])->save();
        $this->get(route('account.dashboard'))->assertOk()
            ->assertSee('Prepará tu publicación')->assertSee('Subir mis fotos')
            ->assertSee('Videos — opcional')->assertDontSee('de 2 pasos')
            ->assertDontSee('Instrucciones de pago');

        $profile->bios()->create(['content' => 'Mi biografía', 'status' => 'pending']);
        $profile->photos()->create(['position' => 0, 'is_primary' => false]);
        $this->get(route('account.dashboard'))->assertOk()
            ->assertDontSee('Elegir foto principal')->assertSee('En revisión')
            ->assertSee('Podrás elegir la principal cuando haya una foto aprobada')
            ->assertDontSee('Subir mis fotos');
        $profile->bios()->update(['status' => 'rejected', 'rejection_reason' => 'Revisá el texto']);
        $this->get(route('account.dashboard'))->assertOk()
            ->assertSee('Requiere corrección')->assertSee('Revisá el texto');
    }
}
