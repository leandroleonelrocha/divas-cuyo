<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityDocumentResubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_rejected_model_sees_reason_and_can_resubmit_a_complete_set(): void
    {
        $user = User::factory()->create();
        $user->modelProfile->forceFill([
            'identity_status' => 'rejected',
            'identity_rejection_reason' => 'Falta claridad en el frente.',
        ])->save();

        foreach (['dni_front', 'dni_back', 'selfie'] as $type) {
            $this->actingAs($user)->post(route('identity.documents.store', $user), [
                'type' => $type,
                'document' => UploadedFile::fake()->image($type.'.jpg'),
            ])->assertRedirect();
        }

        $this->actingAs($user)->get(route('identity.show', $user))
            ->assertOk()
            ->assertSee('Falta claridad en el frente.');

        $this->actingAs($user)->post(route('identity.submit', $user))
            ->assertRedirect(route('identity.show', $user));

        $profile = $user->modelProfile->fresh();
        $this->assertSame('pending', $profile->identity_status);
        $this->assertSame('Falta claridad en el frente.', $profile->identity_rejection_reason);
        $this->assertSame('pending', $profile->review_status);
        $this->assertFalse((bool) $profile->is_published);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_rejected_model_cannot_resubmit_without_all_required_documents(): void
    {
        $user = User::factory()->create();
        $user->modelProfile->forceFill(['identity_status' => 'rejected'])->save();

        $this->actingAs($user)
            ->post(route('identity.submit', $user))
            ->assertSessionHasErrors('documents');

        $this->assertSame('rejected', $user->modelProfile->fresh()->identity_status);
    }
}
