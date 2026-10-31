<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Tests\TestCase;

class PublicationProgressTest extends TestCase
{
    public function test_progress_distinguishes_review_rejection_and_missing_payment(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();
        $profile = $user->modelProfile;
        $profile->forceFill(['identity_status' => 'pending'])->save();
        $this->actingAs($user)->get(route('account.dashboard'))->assertOk()
            ->assertSee('Etapas de tu publicación')
            ->assertSee('Inicio')->assertSee('En revisión')->assertSee('Sin pago')
            ->assertSee('Tu publicación no está visible')
            ->assertDontSee('management-personal');
        $profile->forceFill(['identity_status' => 'rejected'])->save();
        $this->get(route('account.dashboard'))->assertOk()->assertSee('Requiere corrección');
        $this->assertSame('rejected', $profile->fresh()->identity_status);
        $this->assertFalse($profile->fresh()->is_published);
    }
}
