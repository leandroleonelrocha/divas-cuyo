<?php

namespace Tests\Feature\Auth;

use App\Models\PolicyAcceptance;
use App\Models\Province;
use App\Models\PublicationType;
use App\Models\User;
use App\Notifications\VerifyModelEmail;
use Database\Seeders\PublicationTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_lists_only_active_provinces_in_alphabetical_order(): void
    {
        $this->withoutVite();
        Province::factory()->create(['name' => 'San Juan']);
        Province::factory()->create(['name' => 'Mendoza']);
        Province::factory()->create(['name' => 'Provincia desactivada', 'is_active' => false]);

        $this->get(route('register.show'))->assertOk()
            ->assertSeeInOrder(['Seleccioná una provincia', 'Mendoza', 'San Juan'])
            ->assertDontSee('Provincia desactivada');
    }

    public function test_registration_rejects_missing_unknown_and_inactive_provinces(): void
    {
        $payload = $this->validPayload();
        Province::findOrFail($payload['province_id'])->update(['is_active' => false]);

        foreach ([null, 999999, $payload['province_id']] as $provinceId) {
            $this->post(route('register.store'), [...$payload, 'province_id' => $provinceId])
                ->assertSessionHasErrors('province_id');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_saves_province_and_uses_its_name_for_location(): void
    {
        Notification::fake();
        $payload = $this->validPayload();
        $payload['location'] = 'Texto enviado que no debe guardarse';

        $this->post(route('register.store'), $payload)->assertRedirect(route('registration.pending'));

        $user = User::where('email', $payload['email'])->firstOrFail();
        $this->assertSame('Mendoza', $user->location);
        $this->assertSame('Mendoza', $user->modelProfile->location);
        $this->assertSame($payload['province_id'], $user->modelProfile->province_id);
    }

    public function test_registration_shows_available_modalities_and_prices(): void
    {
        $this->withoutVite();
        $this->seed(PublicationTypeSeeder::class);

        $this->get(route('register.show'))->assertOk()
            ->assertSee('Solicitud de incorporación')
            ->assertSee('Solo Virtual')->assertSee('Encuentros')
            ->assertSee('$20.000 ARS mensuales')->assertSee('$30.000 ARS mensuales');
    }

    public function test_virtual_registration_saves_modality_and_history(): void
    {
        Notification::fake();
        $virtual = PublicationType::factory()->create(['slug' => 'virtual']);
        $payload = $this->validPayload();
        $payload['publication_type_id'] = $virtual->id;

        $this->post(route('register.store'), $payload)->assertRedirect(route('registration.pending'));

        $profile = User::where('email', $payload['email'])->firstOrFail()->modelProfile;
        $this->assertSame($virtual->id, $profile->publication_type_id);
        $this->assertSame($virtual->id, $profile->publicationTypeHistory()->firstOrFail()->to_publication_type_id);
    }

    public function test_registration_rejects_missing_unknown_and_inactive_modalities(): void
    {
        $payload = $this->validPayload();
        PublicationType::findOrFail($payload['publication_type_id'])->update(['is_active' => false]);

        foreach ([null, 999999, $payload['publication_type_id']] as $typeId) {
            $this->post(route('register.store'), [...$payload, 'publication_type_id' => $typeId])
                ->assertSessionHasErrors('publication_type_id');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_model_can_register_with_both_policy_acceptances(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(route('registration.pending'));
        $this->assertDatabaseHas('users', [
            'email' => 'modelo@example.com',
            'whatsapp' => '+54 9 261 555 1234',
            'location' => 'Mendoza',
            'is_published' => false,
            'email_verified_at' => null,
        ]);
        $this->assertSame(2, PolicyAcceptance::query()->count());
        $this->assertSame('encounters', User::where('email', 'modelo@example.com')->firstOrFail()->modelProfile->publicationType->slug);
        Notification::assertSentTo(User::where('email', 'modelo@example.com')->first(), VerifyModelEmail::class);
    }

    public function test_registration_requires_both_policy_acceptances(): void
    {
        $response = $this->from(route('register.show'))->post(route('register.store'), [
            ...$this->validPayload(),
            'privacy_accepted' => false,
        ]);

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors('privacy_accepted');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'modelo@example.com']);

        $response = $this->from(route('register.show'))->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_requires_eight_character_password(): void
    {
        $response = $this->from(route('register.show'))->post(route('register.store'), [
            ...$this->validPayload(),
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors('password');
    }

    public function test_registration_requires_all_model_fields(): void
    {
        $response = $this->from(route('register.show'))->post(route('register.store'), []);

        $response->assertRedirect(route('register.show'));
        $response->assertSessionHasErrors(['email', 'password', 'name', 'whatsapp', 'province_id']);
    }

    private function validPayload(): array
    {
        return [
            'email' => 'modelo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Modelo Mendoza',
            'whatsapp' => '+54 9 261 555 1234',
            'location' => 'Mendoza',
            'province_id' => Province::factory()->create(['name' => 'Mendoza'])->id,
            'publication_type_id' => PublicationType::factory()->create(['slug' => 'encounters', 'is_active' => true])->id,
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ];
    }
}
