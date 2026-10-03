<?php

namespace Tests\Feature\PublicModels;

use App\Models\User;
use App\Services\ModelProfileBioModerationService;
use App\Services\ModelProfilePhysicalModerationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelApprovedContentTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_only_current_bio_is_visible_until_replacement_is_approved(): void
    {
        $profile = $this->publicProfile();
        $admin = User::factory()->create(['is_admin' => true]);
        $service = app(ModelProfileBioModerationService::class);
        $current = $service->submit($profile, $profile->user, 'BIO_PUBLICA_VIGENTE');
        $service->approve($current, $admin);
        $pending = $service->submit($profile, $profile->user, 'BIO_PROPUESTA_PRIVADA');
        $this->get('/modelos/mia')->assertOk()->assertSee('BIO_PUBLICA_VIGENTE')->assertDontSee('BIO_PROPUESTA_PRIVADA');
        $service->reject($pending, $admin, 'MOTIVO_PRIVADO');
        $this->get('/modelos/mia')->assertOk()->assertSee('BIO_PUBLICA_VIGENTE')->assertDontSee('MOTIVO_PRIVADO');
        $new = $service->resubmit($pending, $profile->user, 'BIO_NUEVA_APROBADA');
        $service->approve($new, $admin);
        $response = $this->get('/modelos/mia')->assertOk()->assertSee('BIO_NUEVA_APROBADA')->assertDontSee('BIO_PUBLICA_VIGENTE');
        $this->assertSame('BIO_NUEVA_APROBADA', $response->viewData('publicProfile')->bio);
    }

    public static function invalidBios(): array
    {
        return array_map(fn ($case) => [$case], ['absent', 'pending', 'rejected', 'foreign', 'blank', 'historical']);
    }

    #[DataProvider('invalidBios')]
    public function test_missing_or_ineligible_bio_omits_section_without_hiding_profile(string $case): void
    {
        $profile = $this->publicProfile();
        $owner = $case === 'foreign' ? User::factory()->create()->modelProfile : $profile;
        $bio = $owner->bios()->create([
            'content' => $case === 'blank' ? " \n " : 'BIO_NO_PUBLICABLE',
            'status' => in_array($case, ['pending', 'rejected']) ? $case : 'approved',
        ]);
        if (! in_array($case, ['absent', 'historical'])) {
            $profile->update(['current_bio_id' => $bio->id]);
        }
        $response = $this->get('/modelos/mia')->assertOk()->assertDontSee('Sobre mí')->assertDontSee('BIO_NO_PUBLICABLE');
        $this->assertNull($response->viewData('publicProfile')->bio);
    }

    public function test_physical_revisions_only_change_public_characteristics_after_approval(): void
    {
        $profile = $this->publicProfile();
        $current = ['height_cm' => 168, 'weight_kg' => 58.5, 'measurements' => '90-60-90', 'eye_color' => 'Verdes', 'hair_color' => 'Castaño', 'skin_color' => 'Clara', 'body_type' => 'Atlética', 'nationality' => 'Argentina'];
        $profile->update($current);
        $expected = ['Altura' => '1,68 m', 'Peso' => '58,5 kg', 'Medidas' => '90-60-90', 'Ojos' => 'Verdes', 'Cabello' => 'Castaño', 'Piel' => 'Clara', 'Tipo de cuerpo' => 'Atlética', 'Nacionalidad' => 'Argentina'];
        $service = app(ModelProfilePhysicalModerationService::class);
        $admin = User::factory()->create(['is_admin' => true]);
        $proposed = array_replace($current, ['height_cm' => 172, 'hair_color' => 'CABELLO_PROPUESTO']);
        $revision = $service->submit($profile, $profile->user, $proposed);
        $before = $profile->fresh()->getAttributes();
        $response = $this->get('/modelos/mia')->assertOk()->assertSee('1,68 m')->assertDontSee('CABELLO_PROPUESTO');
        $this->assertSame($expected, $response->viewData('publicProfile')->characteristics);
        foreach ($expected as $label => $value) {
            $response->assertSee($label)->assertSee($value);
        }
        $this->assertSame($before, $profile->fresh()->getAttributes());
        $service->reject($revision, $admin, 'MOTIVO_FISICO_PRIVADO');
        $this->get('/modelos/mia')->assertSee('Castaño')->assertDontSee('CABELLO_PROPUESTO')->assertDontSee('MOTIVO_FISICO_PRIVADO');
        $next = $service->submit($profile, $profile->user, $proposed);
        $service->approve($next, $admin);
        $this->get('/modelos/mia')->assertOk()->assertSee('1,72 m')->assertSee('CABELLO_PROPUESTO')->assertDontSee('1,68 m');
    }

    public function test_optional_values_are_omitted_and_approved_text_is_escaped_with_line_breaks(): void
    {
        $profile = $this->publicProfile();
        $profile->update(['hair_color' => '   ', 'eye_color' => '', 'nationality' => null]);
        $response = $this->get('/modelos/mia')->assertOk()->assertDontSee('Características');
        $this->assertSame([], $response->viewData('publicProfile')->characteristics);
        $bio = $profile->bios()->create(['content' => "Primera línea\n<script>alert('bio')</script>", 'status' => 'approved']);
        $profile->update(['current_bio_id' => $bio->id, 'hair_color' => '<img src=x onerror=alert(1)>']);
        $this->get('/modelos/mia')->assertOk()->assertSee('Sobre mí')->assertSee('Primera línea<br', false)
            ->assertSee("<script>alert('bio')</script>")->assertDontSee('<script>', false)
            ->assertSee('<img src=x onerror=alert(1)>')->assertDontSee('<img src=x', false)
            ->assertDontSee('<dt>Ojos</dt>', false);
    }
}
