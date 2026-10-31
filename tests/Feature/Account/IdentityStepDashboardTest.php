<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Tests\TestCase;

class IdentityStepDashboardTest extends TestCase
{
    public function test_dashboard_directs_to_first_pending_step_and_shows_review_without_claiming_publication(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $profile = $user->modelProfile;
        $profile->forceFill(['stage_name' => null, 'slug' => null, 'identity_status' => 'incomplete'])->save();
        $this->actingAs($user);
        $response = $this->get(route('account.dashboard'))->assertOk()
            ->assertSee('0 de 2 pasos')->assertSee('Mis datos')->assertSee('Mi identidad')
            ->assertDontSee('Estados de tu perfil')->assertDontSee('instrucciones-pago')
            ->assertDontSee('dashboard-completion')->assertDontSee('dashboard-status-grid');
        $this->assertStringContainsString(
            'class="account-primary-action stage-primary-action" href="'.route('account.profile.edit').'"',
            $response->getContent()
        );

        $profile->forceFill(['stage_name' => 'Mia', 'slug' => 'mia'])->save();
        $response = $this->get(route('account.dashboard'))->assertOk()->assertSee('1 de 2 pasos');
        $this->assertStringContainsString(
            'class="account-primary-action stage-primary-action" href="'.route('identity.show', $user).'"',
            $response->getContent()
        );

        $profile->forceFill(['identity_status' => 'pending'])->save();
        $this->get(route('account.dashboard'))->assertOk()->assertSee('2 de 2 pasos')
            ->assertSee('Tu identidad está en revisión.')
            ->assertDontSee('Comenzar a completar mi perfil');

        $profile->forceFill(['identity_status' => 'rejected', 'identity_rejection_reason' => 'Documento ilegible'])->save();
        $this->get(route('account.dashboard'))->assertOk()->assertSee('1 de 2 pasos')
            ->assertSee('Documento ilegible')->assertSee('Corregir documentación');

        $profile->forceFill(['identity_status' => 'approved', 'review_status' => 'pending'])->save();
        $this->get(route('account.dashboard'))->assertOk()->assertSee('Tu perfil está en revisión.');
        $profile->forceFill(['review_status' => 'rejected'])->save();
        $this->get(route('account.dashboard'))->assertOk()->assertSee('Tu perfil requiere correcciones.');
        $profile->forceFill(['review_status' => 'approved', 'is_published' => true])->save();
        $this->get(route('account.dashboard'))->assertOk()
            ->assertSee('Prepará tu publicación')
            ->assertSee('Subir mis fotos')
            ->assertDontSee('2 de 2 pasos')
            ->assertDontSee('Tu perfil está publicado y visible en el sitio.');
    }
}
