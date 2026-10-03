<?php

namespace Tests\Feature\PublicModels;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PublicModelProfileFixtures;
use Tests\TestCase;

class PublicModelProfileVisibilityTest extends TestCase
{
    use PublicModelProfileFixtures;

    public function test_guest_can_read_basic_public_profile_without_mutating_it(): void
    {
        $profile = $this->publicProfile();
        $before = $profile->getAttributes();
        $this->get('/modelos/mia')->assertOk()->assertSee('Mia')->assertSee('Modelo verificada por Divas Cuyo')
            ->assertSee('Disponible')->assertSee('Mendoza')->assertSee('Ciudad de Mendoza')->assertSee('Zona centro');
        $this->assertSame($before, $profile->fresh()->getAttributes());
    }

    public static function hiddenStates(): array
    {
        return [
            ['identity_status', 'incomplete'], ['identity_status', 'pending'], ['identity_status', 'rejected'],
            ['identity_status', 'unknown'], ['review_status', 'pending'], ['review_status', 'rejected'],
            ['review_status', 'unknown'], ['is_published', false], ['stage_name', null], ['stage_name', '   '],
        ];
    }

    #[DataProvider('hiddenStates')]
    public function test_non_public_profile_is_not_found(string $field, mixed $value): void
    {
        $profile = $this->publicProfile();
        $profile->forceFill([$field => $value])->save();
        $this->get('/modelos/mia')->assertNotFound()->assertDontSee('Mia')->assertHeaderMissing('Location');
    }

    public function test_email_must_be_verified(): void
    {
        $profile = $this->publicProfile();
        $profile->user->forceFill(['email_verified_at' => null])->save();
        $this->get('/modelos/mia')->assertNotFound();
    }

    public function test_unavailable_profile_remains_public_and_published(): void
    {
        $profile = $this->publicProfile();
        $profile->update(['availability_status' => 'unavailable']);
        $this->get('/modelos/mia')->assertOk()->assertSee('No disponible');
        $this->assertTrue($profile->fresh()->is_published);
    }

    public function test_session_never_bypasses_public_rules(): void
    {
        $profile = $this->publicProfile();
        $profile->update(['is_published' => false]);
        $this->actingAs($profile->user)->get('/modelos/mia')->assertNotFound();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/modelos/mia')->assertNotFound();
    }

    public function test_missing_user_never_resolves(): void
    {
        $profile = $this->publicProfile();
        $profile->user->delete();
        $this->get('/modelos/mia')->assertNotFound();
    }
}
