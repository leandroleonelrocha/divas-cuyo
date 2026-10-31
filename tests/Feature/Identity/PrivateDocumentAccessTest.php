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
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni.jpg'),
        ])->assertRedirect();

        $document = $user->modelProfile->documents()->firstOrFail();

        $this->get('/storage/'.$document->storage_path)->assertForbidden();
    }

    public function test_another_model_cannot_download_a_private_document(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $other = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($owner)->post(route('identity.documents.store', $owner), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni.jpg'),
        ]);
        $document = $owner->modelProfile->documents()->firstOrFail();

        $this->actingAs($other)
            ->get(route('identity.documents.download', [$owner, $document]))
            ->assertForbidden();
    }
    public function test_preview_is_inline_for_admin_and_forbidden_for_other_users(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->withCompletedRegistration()->create();
        $this->actingAs($owner)->post(route('identity.documents.store', $owner), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni.jpg'),
        ]);
        $document = $owner->modelProfile->documents()->firstOrFail();
        $url = route('identity.documents.download', [$owner, $document, 'preview' => 1]);

        $response = $this->actingAs($admin)->get($url)->assertOk();
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $download = $this->get(route('identity.documents.download', [$owner, $document]))->assertOk();
        $this->assertStringStartsWith('attachment;', $download->headers->get('Content-Disposition'));
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($admin)->get(route('identity.documents.download', [$other, $document, 'preview' => 1]))->assertNotFound();
    }

}
