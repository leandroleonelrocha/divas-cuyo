<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Database\Seeders\PublicationTypeSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelProfileAccountPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_profile_panel_renders_sections_empty_states_and_responsive_contract(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertSee('Información privada')
            ->assertSee('Datos públicos')
            ->assertSee('Presentación')
            ->assertSee('Todavía no tenés una biografía aprobada.')
            ->assertSee('profile-fields')
            ->assertSee('aria-labelledby', false)
            ->assertDontSee('Guardar servicios')
            ->assertDontSee('publication-type-form', false)
            ->assertDontSee('#services', false);
    }

    public function test_services_page_renders_own_view_and_sidebar_state(): void
    {
        $this->seed([PublicationTypeSeeder::class, ServiceSeeder::class]);
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('account.services.edit'))
            ->assertOk()
            ->assertSee('Tipo de publicación')
            ->assertSee('Actualizar tipo de publicación')
            ->assertSee('Servicios ofrecidos')
            ->assertSee('Guardar servicios')
            ->assertSee('Servicios virtuales')
            ->assertSee('publication-type-form', false)
            ->assertSee('profile-service-group', false)
            ->assertSee('<a class="is-active" href="'.route('account.services.edit').'"', false)
            ->assertSee('Servicios y tipo de publicación');
    }

    public function test_profile_panel_reports_success_and_validation_errors(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

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
        $owner = User::factory()->withCompletedRegistration()->create(['email' => 'owner@example.com']);
        $other = User::factory()->withCompletedRegistration()->create(['email' => 'other@example.com']);

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
        $owner = User::factory()->withCompletedRegistration()->create();
        $other = User::factory()->withCompletedRegistration()->create();
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
