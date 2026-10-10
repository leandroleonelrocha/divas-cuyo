<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\ModelProfileStats;
use App\Models\ModelProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelProfileStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_use_identity_and_publication_independently_and_exclude_admins(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->modelProfile->forceFill(['identity_status' => 'approved', 'is_published' => true])->save();
        foreach ([['approved', true], ['approved', false], ['pending', true], ['rejected', false], ['approved', false]] as [$identity, $published]) {
            $user = User::factory()->create(['is_admin' => false]);
            $user->modelProfile->forceFill(['identity_status' => $identity, 'is_published' => $published])->save();
        }
        $this->actingAs($admin);
        $stats = new class extends ModelProfileStats
        {
            public function values(): array
            {
                return array_map(fn ($stat) => $stat->getValue(), $this->getStats());
            }
        };
        $this->assertSame([3, 2], $stats->values());
        $this->get('/admin')->assertOk()->assertSee('Modelos verificados')->assertSee('Modelos publicados');

        ModelProfile::query()->delete();
        $this->assertSame([0, 0], $stats->values());
    }

    public function test_widget_is_only_visible_to_verified_admins(): void
    {
        $this->assertFalse(ModelProfileStats::canView());
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->assertFalse(ModelProfileStats::canView());
        $this->actingAs(User::factory()->unverified()->create(['is_admin' => true]));
        $this->assertFalse(ModelProfileStats::canView());
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->assertTrue(ModelProfileStats::canView());
    }
}
