<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifySystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_flash_renders_as_a_closable_toast(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->withSession(['success' => 'Los servicios se actualizaron correctamente.'])
            ->get(route('account.services.edit'))
            ->assertOk()
            ->assertSee('data-notify-variant="toast"', false)
            ->assertSee('data-notify-close', false)
            ->assertSee('role="status"', false)
            ->assertSee('Los servicios se actualizaron correctamente.');
    }

    public function test_validation_errors_render_inline_with_alert_role(): void
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
            ->assertSessionHasErrors(['real_first_name', 'stage_name']);

        $this->actingAs($user)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertSee('data-notify-variant="inline"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('Revisá la información indicada.')
            ->assertSee('data-notify-close', false);
    }

    public function test_duplicate_toast_messages_are_not_rendered_twice(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Los servicios se actualizaron correctamente.'])
            ->get(route('account.services.edit'));

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'data-notify-key="success||Los servicios se actualizaron correctamente."'));
    }
}
