<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_uploaded_document_uses_a_random_private_path(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni-nombre-apellido.jpg'),
        ])->assertRedirect();

        $document = $user->modelProfile->documents()->firstOrFail();

        $this->assertStringStartsWith("profiles/{$user->modelProfile->getKey()}/", $document->storage_path);
        $this->assertStringNotContainsString('dni-nombre-apellido', $document->storage_path);
        Storage::disk('identity_private')->assertExists($document->storage_path);
    }
}
