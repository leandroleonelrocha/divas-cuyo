<?php

namespace Tests\Feature\Profile;

use App\Models\ModelProfilePublicationTypeHistory;
use App\Models\PublicationType;
use App\Models\User;
use App\Services\PublicationTypeChangeService;
use Database\Seeders\PublicationTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublicationTypeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_transitions_keep_actor_source_reason_and_timestamp(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $encounters = PublicationType::query()->where('slug', 'encounters')->firstOrFail();
        $profile = $user->modelProfile;
        $profile->update(['publication_type_id' => $virtual->id]);

        app(PublicationTypeChangeService::class)->change($profile, $encounters, $user, 'model', 'Cambio solicitado');
        app(PublicationTypeChangeService::class)->change($profile->fresh(), $virtual, $user, 'admin', 'Corrección administrativa');

        $history = ModelProfilePublicationTypeHistory::query()->orderBy('changed_at')->get();
        $this->assertCount(2, $history);
        $this->assertSame('model', $history[0]->source);
        $this->assertSame($user->id, $history[0]->changed_by_user_id);
        $this->assertSame('Cambio solicitado', $history[0]->reason);
        $this->assertNotNull($history[0]->changed_at);
        $this->assertSame('admin', $history[1]->source);
        $this->assertSame($virtual->id, $history[1]->to_publication_type_id);
    }

    public function test_noop_does_not_create_history(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $user->modelProfile->update(['publication_type_id' => $virtual->id]);

        app(PublicationTypeChangeService::class)->change($user->modelProfile, $virtual, $user);

        $this->assertDatabaseCount('model_profile_publication_type_history', 0);
    }

    public function test_invalid_source_does_not_change_type_or_create_history(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $user = User::factory()->create();
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $encounters = PublicationType::query()->where('slug', 'encounters')->firstOrFail();
        $user->modelProfile->update(['publication_type_id' => $virtual->id]);

        $this->expectException(ValidationException::class);
        app(PublicationTypeChangeService::class)->change($user->modelProfile, $encounters, $user, 'invalid');

        $this->assertSame($virtual->id, $user->modelProfile->fresh()->publication_type_id);
        $this->assertDatabaseCount('model_profile_publication_type_history', 0);
    }
}
