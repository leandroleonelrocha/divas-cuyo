<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_view_its_own_account(): void
    {
        $user = User::factory()->create();

        $this->assertTrue((new UserPolicy)->view($user, $user));
    }

    public function test_model_cannot_view_another_models_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertFalse((new UserPolicy)->view($user, $other));
    }
}
