<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelAccountPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_model_can_view_its_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Modelo Propia',
            'email' => 'propia@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('propia@example.com')
            ->assertSee('Modelo Propia');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('account.dashboard'))
            ->assertRedirect(route('login.show'));
    }

    public function test_unverified_user_cannot_view_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_account_does_not_accept_external_ids_or_cross_account_access(): void
    {
        $user = User::factory()->create(['email' => 'propia@example.com']);
        $other = User::factory()->create(['email' => 'otra@example.com']);

        $response = $this->actingAs($user)
            ->get(route('account.dashboard', [
                'user_id' => $other->id,
                'model_profile_id' => $other->modelProfile->id,
            ]));

        $response->assertOk()
            ->assertSee('propia@example.com')
            ->assertDontSee('otra@example.com');
    }

    public function test_verified_user_without_profile_sees_controlled_screen(): void
    {
        $user = User::factory()->create();
        $user->modelProfile()->delete();

        $this->actingAs($user)
            ->get(route('account.incomplete-profile'))
            ->assertOk()
            ->assertSee('Tu perfil todavía no está disponible');
    }

    public function test_dashboard_shows_independent_profile_states_and_identity_link(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $profile = $user->modelProfile;
        $profile->forceFill([
            'identity_status' => 'rejected',
            'identity_rejection_reason' => 'La documentación debe corregirse.',
            'review_status' => 'pending',
            'is_published' => false,
        ])->save();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Verificado')
            ->assertSee('Requiere corrección')
            ->assertSee('Pendiente')
            ->assertSee('No publicado')
            ->assertSee('La documentación debe corregirse.')
            ->assertSee(route('identity.show', $user), false)
            ->assertDontSee('storage_path')
            ->assertDontSee('password');
    }
}
