<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Services\IdentityDocumentService;
use App\Services\PrivateIdentityDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityDocumentReplacementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_model_can_replace_a_document_in_incomplete_and_only_the_new_file_remains(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('old.jpg'),
        ]);
        $old = $user->modelProfile->documents()->firstOrFail();

        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect();

        $current = $user->modelProfile->documents()->firstOrFail();
        $this->assertNotSame($old->storage_path, $current->storage_path);
        $this->assertFalse(Storage::disk('identity_private')->exists($old->storage_path));
        $this->assertTrue(Storage::disk('identity_private')->exists($current->storage_path));
        $this->assertDatabaseCount('model_documents', 1);
        $this->assertMatchesRegularExpression('/^profiles\/\d+\/[A-Za-z0-9]{40}\.png$/', $current->storage_path);
    }

    public function test_model_can_replace_a_document_after_rejection(): void
    {
        $user = User::factory()->create();
        $user->modelProfile->forceFill([
            'identity_status' => 'rejected',
            'identity_rejection_reason' => 'El DNI no se lee.',
        ])->save();

        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('corrected.jpg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('model_documents', ['type' => 'dni_front', 'mime_type' => 'image/jpeg']);
        $this->assertDatabaseCount('model_documents', 1);
    }

    public function test_replacement_is_blocked_in_pending_and_approved(): void
    {
        foreach (['pending', 'approved'] as $status) {
            $user = User::factory()->create();
            $this->actingAs($user)->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->image('old.jpg'),
            ]);
            $old = $user->modelProfile->documents()->firstOrFail();
            $user->modelProfile->forceFill(['identity_status' => $status])->save();

            $this->actingAs($user)->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->image('new.jpg'),
            ])->assertSessionHasErrors('document');

            $this->assertSame($old->storage_path, $user->modelProfile->documents()->firstOrFail()->storage_path);
            $this->assertTrue(Storage::disk('identity_private')->exists($old->storage_path));
        }
    }

    public function test_failed_replacement_keeps_the_previous_file_and_record(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('old.jpg'),
        ]);
        $old = $user->modelProfile->documents()->firstOrFail();

        $this->mock(PrivateIdentityDocumentStorage::class, function ($mock): void {
            $mock->shouldReceive('store')->once()->andThrow(new \RuntimeException('storage unavailable'));
        });

        try {
            app(IdentityDocumentService::class)->upload(
                $user->modelProfile,
                'dni_front',
                UploadedFile::fake()->image('new.jpg'),
            );
            $this->fail('Expected the replacement to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('storage unavailable', $exception->getMessage());
        }

        $this->assertTrue(Storage::disk('identity_private')->exists($old->storage_path));
        $this->assertSame($old->storage_path, $user->modelProfile->documents()->firstOrFail()->storage_path);
    }
}
