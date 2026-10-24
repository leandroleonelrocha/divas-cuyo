<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityDocumentAutoUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity_private');
    }

    public function test_ajax_upload_answers_with_json_and_a_private_download_url(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $response = $this->actingAs($user)
            ->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->image('dni.jpg'),
            ], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()
            ->assertJson(['type' => 'dni_front'])
            ->assertJsonPath('download_url', route('identity.documents.download', [$user, $user->modelProfile->documents()->where('type', 'dni_front')->firstOrFail()]));
    }

    public function test_ajax_upload_reports_validation_failures_as_json_errors(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->create('documento.pdf', 5121, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['document']);

        $this->actingAs($user)
            ->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->create('fake.jpg', 10, 'text/plain'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['document']);

        $this->assertDatabaseCount('model_documents', 0);
    }

    public function test_ajax_upload_rejects_changes_while_the_identity_is_under_review(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $user->modelProfile->forceFill(['identity_status' => 'pending'])->save();

        $this->actingAs($user)
            ->post(route('identity.documents.store', $user), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->image('dni.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'La documentación no puede modificarse en el estado actual.');
    }

    public function test_identity_screen_shows_progress_and_blocks_the_submission_until_three_documents_are_loaded(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('identity.show', $user))
            ->assertOk()
            ->assertSee('0 de 3 documentos cargados')
            ->assertSee('data-identity-submit', false)
            ->assertSee('Pendiente')
            ->assertSee('Elegir imagen')
            ->assertSee('Faltan DNI frente, DNI dorso, Selfie para poder enviar.')
            ->assertSee('data-submit-button disabled', false);

        foreach (['dni_front', 'dni_back'] as $type) {
            $this->actingAs($user)->post(route('identity.documents.store', $user), [
                'type' => $type,
                'document' => UploadedFile::fake()->image($type.'.jpg'),
            ])->assertRedirect();
        }

        $this->actingAs($user)
            ->get(route('identity.show', $user))
            ->assertOk()
            ->assertSee('2 de 3 documentos cargados')
            ->assertSee('Faltan Selfie para poder enviar.')
            ->assertSee('data-submit-button disabled', false);

        $this->actingAs($user)->post(route('identity.documents.store', $user), [
            'type' => 'selfie',
            'document' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertRedirect();

        $this->actingAs($user)
            ->get(route('identity.show', $user))
            ->assertOk()
            ->assertSee('3 de 3 documentos cargados')
            ->assertSee('Los 3 documentos están cargados')
            ->assertDontSee('data-submit-button disabled', false)
            ->assertSee('data-submit-button', false)
            ->assertSee('Cambiar imagen');
    }

    public function test_identity_screen_reports_the_pending_state_after_the_submission(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        foreach (['dni_front', 'dni_back', 'selfie'] as $type) {
            $this->actingAs($user)->post(route('identity.documents.store', $user), [
                'type' => $type,
                'document' => UploadedFile::fake()->image($type.'.jpg'),
            ])->assertRedirect();
        }

        $this->actingAs($user)
            ->post(route('identity.submit', $user))
            ->assertRedirect(route('identity.show', $user));

        $this->actingAs($user)
            ->get(route('identity.show', $user))
            ->assertOk()
            ->assertSee('Pendiente de revisión')
            ->assertSee('La documentación fue enviada a revisión.')
            ->assertSee('La documentación está en revisión.')
            ->assertDontSee('data-identity-submit', false);
    }

    public function test_the_upload_endpoint_stays_private_for_guests_and_other_models(): void
    {
        $owner = User::factory()->withCompletedRegistration()->create();
        $other = User::factory()->withCompletedRegistration()->create();

        $this->postJson(route('identity.documents.store', $owner), [
            'type' => 'dni_front',
            'document' => UploadedFile::fake()->image('dni.jpg'),
        ])->assertUnauthorized();

        $this->actingAs($other)
            ->postJson(route('identity.documents.store', $owner), [
                'type' => 'dni_front',
                'document' => UploadedFile::fake()->image('dni.jpg'),
            ])->assertForbidden();

        $this->assertDatabaseCount('model_documents', 0);
    }
}
