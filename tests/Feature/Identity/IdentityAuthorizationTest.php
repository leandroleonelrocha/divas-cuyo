<?php

namespace Tests\Feature\Identity;

use App\Models\ModelDocument;
use App\Models\User;
use App\Services\IdentityDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_guest_cannot_view_submit_upload_or_download_identity_documents(): void
    {
        $owner = User::factory()->create();
        $document = $this->uploadDocument($owner);

        $this->get(route('identity.show', $owner))->assertRedirect(route('login.show'));
        $this->post(route('identity.documents.store', $owner), [
            'type' => 'dni_back',
            'document' => UploadedFile::fake()->image('dni-back.jpg'),
        ])->assertRedirect(route('login.show'));
        $this->post(route('identity.submit', $owner))->assertRedirect(route('login.show'));
        $this->get(route('identity.documents.download', [$owner, $document]))->assertRedirect(route('login.show'));
    }

    public function test_model_cannot_access_another_models_documents_or_change_the_target_profile(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $document = $this->uploadDocument($owner);

        $this->actingAs($other)
            ->get(route('identity.show', $owner))
            ->assertForbidden();
        $this->actingAs($other)
            ->get(route('identity.documents.download', [$owner, $document]))
            ->assertForbidden();
        $this->actingAs($owner)
            ->post(route('identity.documents.store', $owner), [
                'type' => 'dni_back',
                'model_profile_id' => $other->modelProfile->id,
                'document' => UploadedFile::fake()->image('dni-back.jpg'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('model_documents', [
            'model_profile_id' => $owner->modelProfile->id,
            'type' => 'dni_back',
        ]);
        $this->assertDatabaseMissing('model_documents', [
            'model_profile_id' => $other->modelProfile->id,
            'type' => 'dni_back',
        ]);
    }

    public function test_admin_must_be_verified_to_review_or_download_private_documents(): void
    {
        $owner = User::factory()->create();
        $document = $this->uploadDocument($owner);
        $unverifiedAdmin = User::factory()->unverified()->create(['is_admin' => true]);

        $this->actingAs($unverifiedAdmin)
            ->get('/admin/model-profiles/'.$owner->modelProfile->id)
            ->assertForbidden();
        $this->actingAs($unverifiedAdmin)
            ->get(route('identity.documents.download', [$owner, $document]))
            ->assertForbidden();
    }

    private function uploadDocument(User $owner): ModelDocument
    {
        return app(IdentityDocumentService::class)->upload(
            $owner->modelProfile,
            'dni_front',
            UploadedFile::fake()->image('dni-front.jpg'),
        );
    }
}
