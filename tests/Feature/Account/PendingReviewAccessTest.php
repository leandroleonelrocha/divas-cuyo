<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Services\AccountEnrollmentState;
use Tests\TestCase;

class PendingReviewAccessTest extends TestCase
{
    public function test_pending_account_can_only_wait_or_log_out_within_the_panel(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $user->forceFill(['profile_completion_status' => 'pending_review'])->save();
        $this->actingAs($user);
        foreach ([
            ['GET', '/account'], ['GET', '/account/profile'], ['PATCH', '/account/profile'],
            ['GET', '/account/photos'], ['POST', '/account/photos'], ['PATCH', '/account/photos/order'],
            ['POST', '/account/profile/bios'], ['PATCH', '/account/profile/services'],
            ['PATCH', '/account/profile/publication-type'], ['POST', '/account/profile/physical-revisions'],
            ['GET', '/account/incomplete-profile'], ['POST', '/account/incomplete-profile'],
            ['GET', '/cuenta/'.$user->id], ['GET', '/cuenta/'.$user->id.'/identidad'],
            ['POST', '/cuenta/'.$user->id.'/identidad/documentos'], ['POST', '/cuenta/'.$user->id.'/identidad/enviar'],
        ] as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect(route('account.pending-review'));
        }
        $this->postJson(route('account.complete-profile'), ['profile_completion_status' => 'active'])
            ->assertForbidden()->assertJsonPath('redirect_url', route('account.pending-review'));
        $this->get(route('account.pending-review'))->assertOk()->assertSee('Tu perfil está en revisión');
        $this->withCookie(config('legal.cookie'), app(\App\Services\LegalAccess::class)->cookie('accepted')->getValue());
        $this->get(route('terms.show'))->assertOk();
        $this->assertSame('pending_review', $user->fresh()->profile_completion_status);
        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_login_goes_to_waiting_screen_until_admin_approves(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $user->forceFill(['profile_completion_status' => 'pending_review'])->save();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('account.pending-review'));
        $admin = User::factory()->create(['is_admin' => true]);
        app(AccountEnrollmentState::class)->finishReview($user, $admin);
        $this->get(route('account.pending-review'))->assertRedirect(route('account.dashboard'));
        $this->get(route('account.dashboard'))->assertOk();
        app(AccountEnrollmentState::class)->requireCompletion($user, $admin);
        $this->get(route('account.pending-review'))->assertRedirect(route('account.incomplete-profile'));
    }

    public function test_waiting_screen_requires_verified_session(): void
    {
        $this->get(route('account.pending-review'))->assertRedirect(route('login.show'));
        $this->actingAs(User::factory()->unverified()->create())->get(route('account.pending-review'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_pending_status_blocks_even_if_data_is_missing(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['profile_completion_status' => 'pending_review'])->save();
        $this->actingAs($user)->get(route('account.dashboard'))->assertRedirect(route('account.pending-review'));
    }
}
