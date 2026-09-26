<?php

namespace Tests\Feature\Profile;

use App\Models\ModelDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityRevalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_identity_change_resets_resolution_and_preserves_documents(): void
    {
        Storage::fake('identity_private');
        $user = User::factory()->create();
        $profile = $user->modelProfile;
        $reviewer = User::factory()->create(['is_admin' => true]);
        $profile->privateDetails()->create([
            'real_first_name' => 'Mía',
            'real_last_name' => 'Original',
            'birth_date' => '1990-05-20',
        ]);
        $document = new ModelDocument;
        $document->modelProfile()->associate($profile);
        $document->forceFill([
            'type' => 'dni_front',
            'storage_path' => 'profiles/1/dni.jpg',
            'original_name' => 'dni.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 100,
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ])->save();
        $profile->forceFill([
            'identity_status' => 'approved',
            'identity_reviewed_at' => now(),
            'identity_reviewed_by' => $reviewer->id,
            'identity_rejection_reason' => null,
            'is_published' => true,
            'review_status' => 'approved',
        ])->save();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            'real_first_name' => 'Mía Nueva',
            'real_last_name' => 'Original',
            'birth_date' => '1990-05-20',
            'stage_name' => 'Mia',
            'public_age' => null,
            'show_age' => false,
            'nationality' => 'Argentina',
        ])->assertRedirect();

        $profile->refresh();
        $this->assertSame('incomplete', $profile->identity_status);
        $this->assertNull($profile->identity_reviewed_at);
        $this->assertNull($profile->identity_reviewed_by);
        $this->assertNull($profile->identity_rejection_reason);
        $this->assertDatabaseHas('model_documents', [
            'id' => $document->id,
            'storage_path' => 'profiles/1/dni.jpg',
            'status' => 'approved',
        ]);
        $this->assertTrue($profile->is_published);
        $this->assertSame('approved', $profile->review_status);
    }

    public function test_birth_date_change_also_requires_a_new_identity_submission(): void
    {
        $user = User::factory()->create();
        $profile = $user->modelProfile;
        $profile->privateDetails()->create([
            'real_first_name' => 'Mía',
            'real_last_name' => 'Original',
            'birth_date' => '1990-05-20',
        ]);
        $profile->forceFill([
            'identity_status' => 'approved',
            'identity_reviewed_at' => now(),
        ])->save();

        $this->actingAs($user)->patch(route('account.profile.update'), [
            'real_first_name' => 'Mía',
            'real_last_name' => 'Original',
            'birth_date' => '1991-05-20',
            'stage_name' => 'Mia',
            'public_age' => null,
            'show_age' => false,
            'nationality' => 'Argentina',
        ])->assertRedirect();

        $this->assertSame('incomplete', $profile->fresh()->identity_status);
    }
}
