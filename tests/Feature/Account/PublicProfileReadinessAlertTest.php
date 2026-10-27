<?php

namespace Tests\Feature\Account;

use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicProfileReadinessAlertTest extends TestCase
{
    use PublicModelProfileFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('model_photos');
    }

    public function test_visible_profile_does_not_show_the_missing_data_alert(): void
    {
        $profile = $this->readyProfile();

        $this->actingAs($profile->user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertDontSee('todavía no aparece en el sitio público')
            ->assertSee('Tu perfil es visible públicamente.');
    }

    public function test_alert_lists_missing_stage_name_with_a_link_to_fix_it(): void
    {
        $profile = $this->readyProfile();
        $profile->forceFill(['stage_name' => '', 'slug' => null])->save();

        $this->actingAs($profile->user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Tu perfil todavía no aparece en el sitio público')
            ->assertSee('nombre artístico')
            ->assertSee(route('account.profile.edit'), false)
            ->assertSee('Completar nombre');
    }

    public function test_alert_explains_identity_and_review_states(): void
    {
        $profile = $this->readyProfile();
        $profile->forceFill(['identity_status' => 'rejected', 'identity_rejection_reason' => 'borroso', 'review_status' => 'pending'])->save();

        $this->actingAs($profile->user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Tu identidad fue rechazada')
            ->assertSee('en revisión por el equipo');
    }

    public function test_alert_reports_a_missing_primary_photo(): void
    {
        $profile = $this->readyProfile();
        $profile->photos()->delete();

        $this->actingAs($profile->user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Falta marcar una foto como principal')
            ->assertSee(route('account.photos.index'), false);
    }

    public function test_alert_reports_payment_and_publication_state(): void
    {
        $profile = $this->readyProfile();
        $profile->forceFill(['is_published' => false])->save();

        $this->actingAs($profile->user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Falta el pago de publicación')
            ->assertSee('todavía no está publicado en el sitio')
            ->assertSee('#instrucciones-pago', false);
    }

    public function test_expired_payment_shows_the_profile_as_not_visible_even_when_published(): void
    {
        $profile = $this->readyProfile();
        $profile->payments()->create([
            'registered_by' => $profile->user_id,
            'paid_at' => now()->subDays(60),
            'amount' => 1000,
            'period_days' => 30,
            'starts_at' => now()->subDays(60),
            'expires_at' => now()->subDays(30),
        ]);
        $profile->unsetRelation('payments')->unsetRelation('currentPayment');

        $this->actingAs($profile->user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('pago de publicación está vencido')
            ->assertSee('No visible')
            ->assertSee('le faltan datos para mostrarse');
    }

    public function test_published_profile_without_stage_name_is_not_reported_as_visible(): void
    {
        $profile = $this->readyProfile();
        $profile->forceFill(['stage_name' => null, 'slug' => null])->save();

        $this->actingAs($profile->user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('No visible')
            ->assertSee('Falta tu nombre artístico');
    }

    private function readyProfile(): ModelProfile
    {
        $model = User::factory()->withCompletedRegistration()->create();
        $profile = $model->modelProfile;
        $profile->forceFill([
            'stage_name' => 'Mia',
            'slug' => 'mia',
            'identity_status' => 'approved',
            'review_status' => 'approved',
            'is_published' => true,
        ])->save();
        $this->publicPhoto($profile, 0, true);

        return $profile->fresh();
    }
}
