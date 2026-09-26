<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Services\IdentityDocumentService;
use App\Services\PrivateIdentityDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityPrivacyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_private_document_is_not_available_from_public_storage_and_path_traversal_is_ignored(): void
    {
        $owner = User::factory()->create();
        $document = app(IdentityDocumentService::class)->upload(
            $owner->modelProfile,
            'dni_front',
            UploadedFile::fake()->image('dni-front.jpg'),
        );
        $storage = app(PrivateIdentityDocumentStorage::class);
        $maliciousPath = 'profiles/1/../../public/leak.jpg';
        Storage::disk('identity_private')->put('profiles/1/keep.txt', 'private');

        $this->assertFalse($storage->isSafePath($maliciousPath));
        $storage->delete($maliciousPath);
        $this->assertTrue(Storage::disk('identity_private')->exists('profiles/1/keep.txt'));
        $this->get('/storage/'.$document->storage_path)->assertForbidden();

        $document->forceFill(['storage_path' => $maliciousPath])->save();
        $this->actingAs($owner)
            ->get(route('identity.documents.download', [$owner, $document]))
            ->assertNotFound();
    }

    public function test_identity_page_and_download_response_do_not_expose_physical_paths(): void
    {
        $owner = User::factory()->create();
        $document = app(IdentityDocumentService::class)->upload(
            $owner->modelProfile,
            'dni_front',
            UploadedFile::fake()->image('dni-front.jpg'),
        );

        $this->actingAs($owner)
            ->get(route('identity.show', $owner))
            ->assertOk()
            ->assertDontSee($document->storage_path);

        $response = $this->actingAs($owner)
            ->get(route('identity.documents.download', [$owner, $document]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('Pragma', 'no-cache');

        $this->assertStringNotContainsString($document->storage_path, (string) $response->headers->get('Content-Disposition'));
    }

    public function test_model_document_serialization_hides_storage_path(): void
    {
        $owner = User::factory()->create();
        $document = app(IdentityDocumentService::class)->upload(
            $owner->modelProfile,
            'dni_front',
            UploadedFile::fake()->image('dni-front.jpg'),
        );

        $this->assertArrayNotHasKey('storage_path', $document->toArray());
        $this->assertArrayNotHasKey('storage_path', $document->jsonSerialize());
    }
}
