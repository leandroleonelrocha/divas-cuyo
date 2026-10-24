<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelPhotoGalleryUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['model-photos.disk' => 'model_photos']);
        Storage::fake('model_photos');
    }

    public function test_ajax_upload_answers_with_the_rendered_card_and_the_new_counter(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $response = $this->actingAs($user)
            ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->image('nueva.jpg', 800, 800)], [
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ]);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('limit', 5)
            ->assertJsonPath('index', 0)
            ->assertJsonPath('status', 'La fotografía se cargó y quedó en revisión.');

        $card = $response->json('card');
        $this->assertIsString($card);
        $this->assertStringContainsString('data-photo-card', $card);
        $this->assertStringContainsString('En revisión', $card);
        $this->assertStringContainsString('Reemplazar', $card);
        $this->assertStringContainsString('Eliminar', $card);
        $this->assertStringContainsString('Foto 1', $card);
        $this->assertStringNotContainsString('original_path', $card);
    }

    public function test_ajax_upload_reports_limit_and_format_errors_as_json(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        foreach (range(1, 5) as $ignored) {
            $this->actingAs($user)
                ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 640, 640)], [
                    'Accept' => 'application/json',
                    'X-CSRF-TOKEN' => csrf_token(),
                ])
                ->assertOk();
        }

        $this->actingAs($user)
            ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->image('extra.jpg', 640, 640)], [
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->actingAs($user)
            ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->create('video.mp4', 10, 'video/mp4')], [
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->assertDatabaseCount('model_photos', 5);
    }

    public function test_ajax_replace_and_delete_keep_the_gallery_in_sync(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $photoId = $this->actingAs($user)
            ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 640, 640)], [
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ])
            ->assertOk()
            ->json('photo');

        $replace = $this->actingAs($user)
            ->post(route('account.photos.replace', $photoId), ['photo' => UploadedFile::fake()->image('reemplazo.jpg', 640, 640)], [
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ]);

        $replace->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('photo', $photoId)
            ->assertJsonPath('status', 'La nueva versión se cargó y quedó en revisión.');
        $this->assertStringContainsString('data-photo-id="'.$photoId.'"', $replace->json('card'));

        $this->actingAs($user)
            ->delete(route('account.photos.destroy', $photoId), [], [
                'Accept' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ])
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('status', 'La fotografía se eliminó correctamente.');

        $this->assertDatabaseCount('model_photos', 0);
    }

    public function test_empty_gallery_shows_the_central_upload_zone(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('account.photos.index'))
            ->assertOk()
            ->assertSee('data-empty="true"', false)
            ->assertSee('data-full="false"', false)
            ->assertSee('Todavía no cargaste fotos')
            ->assertSee('Agregar fotografías')
            ->assertSee('JPEG, PNG o WebP · hasta 10 MB')
            ->assertSee('0 / 5');
    }

    public function test_gallery_shows_cards_counter_and_the_add_more_card(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 640, 640)])
            ->assertRedirect(route('account.photos.index'));

        $this->actingAs($user)
            ->get(route('account.photos.index'))
            ->assertOk()
            ->assertSee('data-empty="false"', false)
            ->assertSee('data-full="false"', false)
            ->assertSee('data-count="1"', false)
            ->assertSee('1 / 5')
            ->assertSee('Agregar más fotos')
            ->assertSee('En revisión')
            ->assertDontSee('Usar como principal', false)
            ->assertDontSee('Todavía no cargaste fotos');
    }

    public function test_full_gallery_hides_the_add_more_card_and_requires_an_approved_primary(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        foreach (range(1, 5) as $ignored) {
            $this->actingAs($user)
                ->post(route('account.photos.store'), ['photo' => UploadedFile::fake()->image('foto.jpg', 640, 640)]);
        }

        $this->actingAs($user)
            ->get(route('account.photos.index'))
            ->assertOk()
            ->assertSee('data-full="true"', false)
            ->assertSee('5 / 5')
            ->assertSee('Alcanzaste el máximo de 5 fotografías.')
            ->assertSee('data-empty="false"', false);
    }
}
