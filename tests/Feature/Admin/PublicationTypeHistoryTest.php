<?php

namespace Tests\Feature\Admin;

use App\Models\ModelProfilePublicationTypeHistory;
use App\Models\PublicationType;
use App\Models\User;
use App\Services\PublicationTypeChangeService;
use Database\Seeders\PublicationTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PublicationTypeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_admin_can_view_history_but_model_cannot(): void
    {
        $this->seed(PublicationTypeSeeder::class);
        $model = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $virtual = PublicationType::query()->where('slug', 'virtual')->firstOrFail();
        $encounters = PublicationType::query()->where('slug', 'encounters')->firstOrFail();
        $model->modelProfile->update(['publication_type_id' => $virtual->id]);
        app(PublicationTypeChangeService::class)->change($model->modelProfile, $encounters, $model);
        $history = ModelProfilePublicationTypeHistory::query()->firstOrFail();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $history));
        $this->assertFalse(Gate::forUser($model)->allows('view', $history));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', ModelProfilePublicationTypeHistory::class));
        $this->assertFalse(Gate::forUser($model)->allows('viewAny', ModelProfilePublicationTypeHistory::class));
    }
}
