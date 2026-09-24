<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityDocumentUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_verified_model_can_upload_all_required_documents_and_submit(): void
    {
        $user = User::factory()->create();

        foreach (['dni_front', 'dni_back', 'selfie'] as $type) {
            $this->actingAs($user)
                ->post(route('identity.documents.store', $user), [
                    'type' => $type,
                    'document' => UploadedFile::fake()->image($type.'.jpg'),
                ])
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('identity.submit', $user))
            ->assertRedirect(route('identity.show', $user));

        $this->assertDatabaseHas('model_profiles', [
            'user_id' => $user->id,
            'identity_status' => 'pending',
        ]);

        $this->assertDatabaseCount('model_documents', 3);
        $this->assertDatabaseMissing('model_documents', ['storage_path' => 'dni_front.jpg']);

        $document = $user->modelProfile->documents()->where('type', 'dni_front')->firstOrFail();
        $this->assertNotSame('dni_front.jpg', $document->storage_path);
        $this->get('/storage/'.$document->storage_path)->assertForbidden();

        $this->actingAs($user)
            ->get(route('identity.documents.download', [$user, $document]))
            ->assertOk();
    }

    public function test_upload_rejects_invalid_mime_and_files_over_five_megabytes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->create('fake.jpg', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('document');

        $this->actingAs($user)
            ->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
            ])
            ->assertSessionHasErrors('document');

        $this->assertDatabaseCount('model_documents', 0);
    }

    public function test_upload_accepts_configured_jpeg_png_webp_and_pdf_formats(): void
    {
        $files = [
            UploadedFile::fake()->image('document.jpg'),
            UploadedFile::fake()->image('document.png'),
            UploadedFile::fake()->image('document.webp'),
            UploadedFile::fake()->createWithContent('document.pdf', "%PDF-1.4\nidentity\n%%EOF"),
        ];

        foreach ($files as $file) {
            $user = User::factory()->create();

            $this->actingAs($user)
                ->post(route('identity.documents.store', $user), [
                    'type' => 'dni_front',
                    'document' => $file,
                ])
                ->assertRedirect();
        }

        $this->assertDatabaseCount('model_documents', 4);
    }

    public function test_guest_and_another_model_cannot_upload_for_the_profile(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->post(route('identity.documents.store', $owner), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni.jpg'),
        ])->assertRedirect(route('login.show'));

        $this->actingAs($other)
            ->post(route('identity.documents.store', $owner), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->image('dni.jpg'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('model_documents', 0);
    }

    public function test_pending_identity_blocks_new_uploads(): void
    {
        $user = User::factory()->create();
        $user->modelProfile->forceFill(['identity_status' => 'pending'])->save();

        $this->actingAs($user)
            ->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->image('dni.jpg'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('model_documents', 0);
    }
}
