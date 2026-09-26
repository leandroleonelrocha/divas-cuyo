<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelProfileAccountPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_profile_panel_renders_sections_empty_states_and_responsive_contract(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertSee('Información privada')
            ->assertSee('Datos públicos')
            ->assertSee('Tipo de publicación')
            ->assertSee('Servicios')
            ->assertSee('Presentación')
            ->assertSee('Todavía no tenés una biografía aprobada.')
            ->assertSee('profile-fields')
            ->assertSee('aria-labelledby', false);
    }

    public function test_profile_panel_reports_success_and_validation_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('account.profile.update'), [
                'real_first_name' => '',
                'real_last_name' => '',
                'birth_date' => now()->addDay()->toDateString(),
                'stage_name' => '',
                'public_age' => 100,
                'show_age' => true,
                'nationality' => '',
            ])
            ->assertSessionHasErrors(['real_first_name', 'real_last_name', 'birth_date', 'stage_name', 'nationality']);

        $this->actingAs($user)
            ->patch(route('account.profile.update'), [
                'real_first_name' => 'Mía',
                'real_last_name' => 'Privada',
                'birth_date' => '1990-05-20',
                'stage_name' => 'Mia',
                'public_age' => 35,
                'show_age' => true,
                'nationality' => 'Argentina',
            ])
            ->assertRedirect(route('account.profile.edit'))
            ->assertSessionHas('success');
    }

    public function test_profile_panel_always_resolves_the_authenticated_owner(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $other = User::factory()->create(['email' => 'other@example.com']);

        $this->actingAs($owner)
            ->get(route('account.profile.edit', [
                'user_id' => $other->id,
                'model_profile_id' => $other->modelProfile->id,
            ]))
            ->assertOk()
            ->assertSee($owner->modelProfile->name)
            ->assertDontSee($other->modelProfile->name);
    }

    public function test_rejected_bio_can_be_corrected_through_the_contract_route_without_cross_account_access(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $rejected = $owner->modelProfile->bios()->create([
            'content' => 'Texto rechazado que debe conservarse.',
            'status' => 'rejected',
            'rejection_reason' => 'Revisar el texto.',
        ]);

        $this->actingAs($other)
            ->patch(route('account.profile.bios.update', $rejected), ['content' => 'Intento ajeno inválido'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('account.profile.bios.update', $rejected), ['content' => 'Una presentación corregida y válida.'])
            ->assertRedirect(route('account.profile.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('model_profile_bios', [
            'model_profile_id' => $owner->modelProfile->id,
            'status' => 'pending',
            'content' => 'Una presentación corregida y válida.',
        ]);
        $this->assertDatabaseHas('model_profile_bios', [
            'id' => $rejected->id,
            'status' => 'rejected',
        ]);
    }
}
