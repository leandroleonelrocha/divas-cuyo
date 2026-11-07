<?php

namespace Tests\Feature\Account;

use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Models\User;
use App\Services\ProfileStatistics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\AcceptsLegalDisclaimer;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class ProfileStatisticsTest extends TestCase
{
    use AcceptsLegalDisclaimer, PublicModelProfileFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
    }

    public function test_events_are_deduplicated_for_thirty_minutes_and_contact_implies_visit(): void
    {
        $profile = $this->publicProfile();
        $profile->forceFill(['whatsapp' => '5492611234567'])->save();
        $this->getJson(route('statistics.session'))->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $url = route('statistics.record', $profile->slug);
        $this->withHeader('User-Agent', 'Mozilla/5.0');
        $this->postJson($url, ['event' => 'whatsapp'])->assertNoContent();
        $this->postJson($url, ['event' => 'visit'])->assertNoContent();
        $this->postJson($url, ['event' => 'whatsapp'])->assertNoContent();
        $this->assertDatabaseHas('profile_stat_days', ['model_profile_id' => $profile->id, 'visits' => 1, 'whatsapp_clicks' => 1]);
        $this->travel(31)->minutes();
        $this->postJson($url, ['event' => 'visit'])->assertNoContent();
        $this->assertDatabaseHas('profile_stat_days', ['model_profile_id' => $profile->id, 'visits' => 2, 'whatsapp_clicks' => 1]);
        $this->assertSame(64, strlen(DB::table('profile_stat_visitors')->first()->visitor_hash));
    }

    public function test_admin_owner_bots_and_hidden_profiles_are_not_counted(): void
    {
        $profile = $this->publicProfile();
        $this->getJson(route('statistics.session'))->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $url = route('statistics.record', $profile->slug);
        $this->withHeader('User-Agent', 'Googlebot')->postJson($url, ['event' => 'visit'])->assertNoContent();
        $this->withHeader('User-Agent', 'Mozilla/5.0');
        $this->actingAs($profile->user)->postJson($url, ['event' => 'visit'])->assertNoContent();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->postJson($url, ['event' => 'visit'])->assertNoContent();
        auth()->forgetGuards();
        $profile->forceFill(['is_published' => false])->save();
        $this->postJson($url, ['event' => 'visit'])->assertNotFound();
        $this->assertDatabaseCount('profile_stat_days', 0);
    }

    public function test_daily_dates_follow_argentina_and_filters_fill_empty_days(): void
    {
        $this->travelTo(Carbon::parse('2026-11-06 01:00:00', 'UTC'));
        $profile = $this->publicProfile();
        $service = app(ProfileStatistics::class);
        $service->record($profile, str_repeat('a', 64), 'visit');
        $this->assertDatabaseHas('profile_stat_days', ['day' => '2026-11-05']);
        $stats = $service->summary($profile, '7');
        $this->assertCount(7, $stats['days']);
        $this->assertSame(1, $stats['visits']);
        $this->assertSame(0.0, $stats['rate']);
        $this->assertSame('05/11', $stats['days'][6]['date']);
    }

    public function test_account_statistics_cannot_select_another_profile_and_admin_can_view(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $other = User::factory()->withCompletedRegistration()->create();
        app(ProfileStatistics::class)->record($other->modelProfile, str_repeat('b', 64), 'visit');
        $this->actingAs($owner)->get(route('account.statistics', ['model_profile_id' => $other->modelProfile->id]))
            ->assertOk()->assertViewHas('stats', fn ($stats) => $stats['visits'] === 0)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('account.statistics', ['period' => 'invalid']))->assertSessionHasErrors('period');
        $admin = User::factory()->create(['is_admin' => true]);
        Livewire::actingAs($admin)->test(ViewModelProfile::class, ['record' => $other->modelProfile->id])
            ->set('activeRelationManager', 'statistics')->assertSee('Visitas al perfil')
            ->set('statisticsPeriod', '7')->assertSee('Evolución diaria');
        $this->actingAs($owner)->get('/admin/model-profiles/'.$other->modelProfile->id)->assertForbidden();
    }

    public function test_tracking_validates_events_and_rate_limits_requests(): void
    {
        $profile = $this->publicProfile();
        $this->withHeader('User-Agent', 'Mozilla/5.0');
        $this->postJson(route('statistics.record', $profile->slug), ['event' => 'arbitrary'])->assertUnprocessable();
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson(route('statistics.session'));
        }
        $response->assertStatus(429);
    }
}
