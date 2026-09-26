<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_view_only_its_own_private_account(): void
    {
        $user = User::factory()->create([
            'email' => 'one@example.com',
            'whatsapp' => '+54 9 261 111 1111',
            'location' => 'Mendoza',
        ]);
        $other = User::factory()->create(['email' => 'two@example.com']);

        $this->actingAs($user)
            ->get(route('account.show', ['user' => $user]))
            ->assertOk()
            ->assertSee('one@example.com')
            ->assertSee('+54 9 261 111 1111')
            ->assertDontSee('password');

        $this->actingAs($user)
            ->get(route('account.show', ['user' => $other]))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_private_account(): void
    {
        $user = User::factory()->create();

        $this->get(route('account.show', ['user' => $user]))
            ->assertRedirect(route('login.show'));
    }

    public function test_accounts_are_not_published_by_registration_state(): void
    {
        $user = User::factory()->create(['is_published' => false]);

        $this->assertFalse($user->fresh()->is_published);
        $this->assertRouteIsNotPubliclyListed($user);
    }

    private function assertRouteIsNotPubliclyListed(User $user): void
    {
        $this->get('/perfiles/'.$user->id)->assertNotFound();
    }
}
