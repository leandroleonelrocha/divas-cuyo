<?php

namespace Tests\Feature\Account;

use App\Http\Middleware\EnsureProfileIsComplete;
use App\Models\Province;
use App\Models\PublicationType;
use App\Models\Service;
use App\Models\User;
use App\Notifications\VerifyModelEmail;
use App\Services\ProfileCompletionService;
use Database\Seeders\PublicationTypeSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_full_registration_verification_login_and_completion_flow(): void
    {
        Notification::fake();
        $credentials = ['email' => 'new@example.test', 'password' => 'password123'];
        $this->post(route('register.store'), $credentials + ['password_confirmation' => 'password123'])
            ->assertRedirect(route('registration.pending'));
        $this->post(route('login.store'), $credentials)->assertSessionHasErrors('email');
        $this->assertGuest();
        $user = User::sole();
        $notification = Notification::sent($user, VerifyModelEmail::class)->first();
        $this->get(route('verification.verify', $notification->token))->assertRedirect(route('login.show'));
        $this->post(route('login.store'), $credentials)->assertRedirect(route('account.incomplete-profile'));
        $this->get(route('account.dashboard'))->assertRedirect(route('account.incomplete-profile'));
        $this->get(route('account.incomplete-profile'))->assertOk()->assertSee('Bienvenida a Divas Cuyo');
        $payload = $this->payload();
        $this->post(route('account.complete-profile'), $payload)->assertRedirect(route('account.pending-review'));
        $profile = $user->fresh()->modelProfile;
        $this->assertSame($payload['province_id'], $profile->province_id);
        $this->assertSame($payload['publication_type_id'], $profile->publication_type_id);
        $this->assertSame('Mendoza', $profile->location);
        $this->assertSame('pending', $profile->review_status);
        $this->assertFalse($profile->is_published);
        $this->assertNotSame('approved', $profile->identity_status);
        $this->assertDatabaseCount('policy_acceptances', 2);
        $this->assertDatabaseCount('model_profile_publication_type_history', 1);
        $this->get(route('account.dashboard'))->assertRedirect(route('account.pending-review'));
        $this->get(route('account.photos.index'))->assertRedirect(route('account.pending-review'));
        $this->get(route('account.incomplete-profile'))->assertRedirect(route('account.pending-review'));
        $this->post(route('account.complete-profile'), $payload)->assertRedirect(route('account.pending-review'));
        $this->assertDatabaseCount('model_profiles', 1);
        $this->assertDatabaseCount('model_profile_publication_type_history', 1);
    }

    public function test_incomplete_users_cannot_bypass_the_gate_with_direct_requests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach ([
            ['GET', '/account'], ['GET', '/account/profile'], ['PATCH', '/account/profile'],
            ['PATCH', '/account/profile/services'], ['PATCH', '/account/profile/publication-type'],
            ['POST', '/account/profile/bios'], ['POST', '/account/profile/physical-revisions'],
            ['GET', '/account/photos'], ['POST', '/account/photos'], ['PATCH', '/account/photos/order'],
            ['GET', '/cuenta/'.$user->id], ['GET', '/cuenta/'.$user->id.'/identidad'],
            ['POST', '/cuenta/'.$user->id.'/identidad/documentos'], ['POST', '/cuenta/'.$user->id.'/identidad/enviar'],
        ] as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect(route('account.incomplete-profile'));
        }
        $this->patchJson(route('account.profile.services.update'), ['service_ids' => []])
            ->assertForbidden()->assertJsonPath('redirect_url', route('account.incomplete-profile'));
        $this->withCookie(config('legal.cookie'), app(\App\Services\LegalAccess::class)->cookie('accepted')->getValue());
        $this->get(route('terms.show'))->assertOk();
        $this->get(route('privacy.show'))->assertOk();
        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_completion_requires_authentication_and_verified_email(): void
    {
        $this->get(route('account.incomplete-profile'))->assertRedirect(route('login.show'));
        $this->post(route('account.complete-profile'), [])->assertRedirect(route('login.show'));
        $this->actingAs(User::factory()->unverified()->create());
        $this->get(route('account.incomplete-profile'))->assertRedirect(route('verification.notice'));
        $this->post(route('account.complete-profile'), [])->assertRedirect(route('verification.notice'));
    }

    public function test_completion_reuses_required_fields_and_validates_catalogs(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('account.complete-profile'), [])->assertSessionHasErrors([
            'name', 'whatsapp', 'province_id', 'publication_type_id', 'terms_accepted', 'privacy_accepted',
        ]);
        $payload = $this->payload();
        $other = Province::factory()->create();
        $locality = $other->localities()->create(['name' => 'Otra', 'slug' => 'otra', 'is_active' => true]);
        $this->post(route('account.complete-profile'), $payload + ['locality_id' => $locality->id])
            ->assertSessionHasErrors('locality_id');
        Province::find($payload['province_id'])->update(['is_active' => false]);
        PublicationType::find($payload['publication_type_id'])->update(['is_active' => false]);
        $this->post(route('account.complete-profile'), $payload)
            ->assertSessionHasErrors(['province_id', 'publication_type_id']);
        $this->assertDatabaseCount('policy_acceptances', 0);
    }

    public function test_existing_complete_profiles_keep_access_regardless_of_review_or_publication(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $user->modelProfile->update(['review_status' => 'rejected', 'is_published' => false]);
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk();
        $user->modelProfile->update(['whatsapp' => '   ']);
        $this->get(route('account.dashboard'))->assertRedirect(route('account.incomplete-profile'));
    }

    public function test_completion_preserves_existing_profile_and_ignores_ownership_and_approval_input(): void
    {
        $user = User::factory()->create();
        $profile = $user->modelProfile;
        $profile->forceFill(['review_status' => 'approved', 'identity_status' => 'approved', 'is_published' => true])->save();
        $other = User::factory()->create();
        $this->actingAs($user)->post(route('account.complete-profile'), $this->payload() + [
            'user_id' => $other->id, 'is_admin' => true, 'review_status' => 'rejected',
        ])->assertRedirect(route('account.pending-review'));
        $this->assertSame($profile->id, $user->fresh()->modelProfile->id);
        $this->assertSame('approved', $profile->fresh()->review_status);
        $this->assertTrue($profile->fresh()->is_published);
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertFalse(app(ProfileCompletionService::class)->isComplete($other));
    }

    public function test_admin_routes_do_not_require_model_completion(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->modelProfile()->delete();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_welcome_lists_active_catalogs_and_preserves_input_after_errors(): void
    {
        $user = User::factory()->create();
        Province::factory()->create(['name' => 'San Juan']);
        Province::factory()->create(['name' => 'Mendoza']);
        Province::factory()->create(['name' => 'Oculta', 'is_active' => false]);
        $this->seed(PublicationTypeSeeder::class);
        $this->actingAs($user)->get(route('account.incomplete-profile'))->assertOk()
            ->assertSeeInOrder(['Seleccioná una provincia', 'Mendoza', 'San Juan'])
            ->assertDontSee('Oculta')->assertSee('$20.000 ARS mensuales')->assertSee('$30.000 ARS mensuales');
        $this->from(route('account.incomplete-profile'))->post(route('account.complete-profile'), ['name' => 'Mi nombre'])
            ->assertSessionHasErrors('whatsapp')->assertSessionHasInput('name', 'Mi nombre');
    }

    public function test_all_account_and_identity_actions_have_the_server_gate(): void
    {
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName() ?? '';
            if (! str_starts_with($name, 'account.') && ! str_starts_with($name, 'identity.')) {
                continue;
            }
            if (in_array($name, ['account.incomplete-profile', 'account.complete-profile', 'account.pending-review'], true)) {
                continue;
            }
            $this->assertContains('verified', $route->gatherMiddleware(), $name);
            $this->assertContains(EnsureProfileIsComplete::class, $route->gatherMiddleware(), $name);
        }
    }

    public function test_failed_modality_change_rolls_back_profile_and_account_changes(): void
    {
        $user = User::factory()->create();
        $profile = $user->modelProfile;
        $service = Service::factory()->create(['service_type' => 'in_person']);
        $profile->services()->attach($service);
        $payload = $this->payload();
        $this->actingAs($user)->post(route('account.complete-profile'), $payload)
            ->assertSessionHasErrors('confirm_in_person_removal');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertNull($profile->fresh()->publication_type_id);
        $this->assertDatabaseCount('policy_acceptances', 0);
        $this->assertTrue($profile->services()->whereKey($service)->exists());
        $this->post(route('account.complete-profile'), $payload + ['confirm_in_person_removal' => true])
            ->assertRedirect(route('account.pending-review'));
        $this->assertCount(0, $profile->fresh()->services);
    }

    public function test_existing_catalog_retirement_does_not_make_a_completed_profile_incomplete(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $profile = $user->modelProfile;
        $locality = $profile->province->localities()->create(['name' => 'Ciudad', 'slug' => 'ciudad']);
        $profile->update(['locality_id' => $locality->id]);
        $locality->update(['is_active' => false]);
        $profile->province->update(['is_active' => false]);
        $profile->publicationType->update(['is_active' => false]);
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk();
    }

    private function payload(): array
    {
        return [
            'name' => 'Nombre público', 'whatsapp' => '+54 9 261 555 1234',
            'province_id' => Province::factory()->create(['name' => 'Mendoza'])->id,
            'publication_type_id' => PublicationType::factory()->create(['slug' => 'virtual'])->id,
            'terms_accepted' => '1', 'privacy_accepted' => '1',
        ];
    }
}
