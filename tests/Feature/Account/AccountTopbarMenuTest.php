<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTopbarMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_dropdown_offers_logout_on_account_pages(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        foreach (['account.dashboard', 'account.profile.edit', 'account.services.edit', 'account.photos.index', 'account.videos.index'] as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('data-topbar-menu', false)
                ->assertSee('data-topbar-dropdown', false)
                ->assertSee('data-topbar-toggle', false)
                ->assertSee('action="'.route('logout').'"', false)
                ->assertSee('Cerrar sesión');
        }
    }
}
