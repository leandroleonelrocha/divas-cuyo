<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityDocumentSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_missing_documents_block_submission_and_keep_incomplete(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('identity.submit', $user))
            ->assertSessionHasErrors('documents');

        $this->assertSame('incomplete', $user->modelProfile->fresh()->identity_status);
    }

    public function test_submission_preserves_email_review_and_publication_states(): void
    {
        $user = User::factory()->create();
        $profile = $user->modelProfile;
        $profile->update(['review_status' => 'approved', 'is_published' => true]);

        foreach (['dni_front', 'dni_back', 'selfie'] as $type) {
            $this->actingAs($user)->post(route('identity.documents.store', $user), [
                'type' => $type,
                'document' => UploadedFile::fake()->image($type.'.jpg'),
            ]);
        }

        $this->actingAs($user)->post(route('identity.submit', $user))->assertRedirect();

        $profile->refresh();
        $this->assertSame('pending', $profile->identity_status);
        $this->assertSame('approved', $profile->review_status);
        $this->assertTrue($profile->is_published);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_identity_screen_is_private_and_does_not_expose_public_file_urls(): void
    {
        $user = User::factory()->create();

        $this->get(route('identity.show', $user))
            ->assertRedirect(route('login.show'));

        $this->actingAs($user)
            ->get(route('identity.show', $user))
            ->assertOk()
            ->assertSee('Validación de identidad')
            ->assertDontSee('storage_path');
    }
}
