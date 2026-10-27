<?php

namespace Tests\Feature\Account;

use App\Models\PaymentInstruction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPaymentInstructionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_payment_instructions_next_to_next_steps(): void
    {
        PaymentInstruction::query()->create([
            'alias' => 'divas.cuyo.mp',
            'transfer_details' => 'CVU 000011223344',
            'receipt_whatsapp' => '+54 9 266 555 0000',
            'default_amount' => 25000,
            'default_amount_virtual' => 15000,
            'is_active' => true,
        ]);
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Próximos pasos')
            ->assertSee('Instrucciones de pago')
            ->assertSee('divas.cuyo.mp')
            ->assertSee('Datos de transferencia')
            ->assertSee('Importe publicación')
            ->assertSee('$ 25.000,00')
            ->assertSee('Importe virtual')
            ->assertSee('$ 15.000,00')
            ->assertSee('wa.me/5492665550000', false);
    }

    public function test_dashboard_shows_empty_payment_instructions_state(): void
    {
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('Próximos pasos')
            ->assertSee('Instrucciones de pago')
            ->assertSee('Las instrucciones de pago todavía no están disponibles.')
            ->assertSee('id="instrucciones-pago"', false);
    }

    public function test_inactive_payment_instructions_are_not_shown(): void
    {
        PaymentInstruction::query()->create([
            'alias' => 'alias.inactivo',
            'is_active' => false,
        ]);
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertDontSee('alias.inactivo')
            ->assertSee('Las instrucciones de pago todavía no están disponibles.');
    }

    public function test_empty_active_instruction_does_not_shadow_the_filled_one(): void
    {
        PaymentInstruction::query()->create([
            'alias' => 'alias.mercado.pago',
            'transfer_details' => 'CVU 1111',
            'is_active' => true,
        ]);
        PaymentInstruction::query()->create([
            'alias' => null,
            'transfer_details' => null,
            'is_active' => true,
        ]);
        $user = User::factory()->withCompletedRegistration()->create();

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('alias.mercado.pago')
            ->assertSee('CVU 1111')
            ->assertDontSee('Las instrucciones de pago todavía no están disponibles.');
    }
}
