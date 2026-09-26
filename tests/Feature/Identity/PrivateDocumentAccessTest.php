<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_private_document_cannot_be_read_from_public_storage_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni.jpg'),
        ])->assertRedirect();

        $document = $user->modelProfile->documents()->firstOrFail();

        $this->get('/storage/'.$document->storage_path)->assertForbidden();
    }

    public function test_another_model_cannot_download_a_private_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('identity.documents.store', $owner), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni.jpg'),
        ]);
        $document = $owner->modelProfile->documents()->firstOrFail();

        $this->actingAs($other)
            ->get(route('identity.documents.download', [$owner, $document]))
            ->assertForbidden();
    }
}
