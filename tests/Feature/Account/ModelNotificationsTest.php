<?php

namespace Tests\Feature\Account;

use App\Models\ModelProfile;
use App\Models\User;
use App\Services\IdentityDocumentService;
use App\Services\ModelProfileModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_profile_approval_creates_a_notification_visible_in_the_header(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $model = User::factory()->withCompletedRegistration()->create();
        $profile = $model->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();

        app(ModelProfileModerationService::class)->approve($profile, $admin);

        $this->actingAs($model)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Perfil aprobado')
            ->assertSee('Tu perfil fue aprobado por el equipo.')
            ->assertSee('account-notif-badge')
            ->assertSee('1 nueva');
    }

    public function test_identity_rejection_shows_the_reason_in_the_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $model = User::factory()->withCompletedRegistration()->create();
        $profile = $this->makePendingProfile($model);

        app(IdentityDocumentService::class)->rejectIdentity($profile, $admin, 'El DNI está borroso.');

        $this->actingAs($model)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Identidad rechazada')
            ->assertSee('Motivo: El DNI está borroso.');
    }

    public function test_notifications_are_scoped_to_the_owner_of_the_profile(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->withCompletedRegistration()->create();
        $other = User::factory()->withCompletedRegistration()->create();
        $profile = $owner->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();

        app(ModelProfileModerationService::class)->approve($profile, $admin);

        $this->actingAs($other)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertDontSee('Tu perfil fue aprobado por el equipo.')
            ->assertDontSee('account-notif-badge')
            ->assertSee('Todavía no hay notificaciones');
    }

    public function test_marking_all_as_read_clears_the_badge(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $model = User::factory()->withCompletedRegistration()->create();
        $profile = $model->modelProfile;
        $profile->forceFill(['identity_status' => 'approved'])->save();

        app(ModelProfileModerationService::class)->approve($profile, $admin);

        $this->actingAs($model)
            ->post(route('account.notifications.read'))
            ->assertOk()
            ->assertJson(['unread' => 0]);

        $this->actingAs($model)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertDontSee('account-notif-badge')
            ->assertSee('Perfil aprobado');
    }

    public function test_guests_cannot_mark_notifications_as_read(): void
    {
        $this->post(route('account.notifications.read'))->assertRedirect(route('login.show'));
    }

    private function makePendingProfile(User $model): ModelProfile
    {
        $profile = $model->modelProfile;
        $service = app(IdentityDocumentService::class);

        foreach (['dni_front', 'dni_back', 'selfie'] as $type) {
            $service->upload($profile, $type, UploadedFile::fake()->image($type.'.jpg'));
        }

        $profile->forceFill(['identity_status' => 'pending'])->save();

        return $profile->fresh(['user', 'documents']);
    }
}
