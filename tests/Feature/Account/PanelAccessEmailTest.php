<?php

namespace Tests\Feature\Account;

use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Models\User;
use App\Notifications\PanelAccessEnabled;
use App\Services\AccountEnrollmentState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PanelAccessEmailTest extends TestCase
{
    public function test_admin_action_enables_access_and_emails_only_the_owner(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->withCompletedRegistration()->create();
        $owner->forceFill(['profile_completion_status' => AccountEnrollmentState::PENDING])->save();

        Livewire::actingAs($admin)->test(ListModelProfiles::class)
            ->callTableAction('finishEnrollmentReview', $owner->modelProfile)->assertHasNoErrors();

        $this->assertSame(AccountEnrollmentState::ACTIVE, $owner->fresh()->profile_completion_status);
        Notification::assertSentTo($owner, PanelAccessEnabled::class, function ($notification, $channels) use ($owner) {
            $this->assertSame(['mail'], $channels);
            $mail = $notification->toMail($owner);
            $this->assertSame(route('account.dashboard'), $mail->actionUrl);
            $this->assertStringContainsString('Tu acceso al panel está habilitado', (string) $mail->render());
            $this->assertStringContainsString('Ingresar a mi cuenta', view('emails.account-text', $mail->data())->render());

            return true;
        });
        Notification::assertCount(1);

        try {
            app(AccountEnrollmentState::class)->finishReview($owner, $admin);
            $this->fail('An already active account must not be enabled twice.');
        } catch (ValidationException) {
            Notification::assertCount(1);
        }
    }

    public function test_email_waits_for_commit_and_is_discarded_on_rollback(): void
    {
        config(['queue.default' => 'database']);
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->withCompletedRegistration()->create();
        $owner->forceFill(['profile_completion_status' => AccountEnrollmentState::PENDING])->save();

        try {
            DB::transaction(function () use ($owner, $admin) {
                app(AccountEnrollmentState::class)->finishReview($owner, $admin);
                $this->assertDatabaseCount('jobs', 0);
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback', $exception->getMessage());
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(AccountEnrollmentState::PENDING, $owner->fresh()->profile_completion_status);

        app(AccountEnrollmentState::class)->finishReview($owner, $admin);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_requiring_completion_does_not_send_an_access_email(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->withCompletedRegistration()->create();
        app(AccountEnrollmentState::class)->requireCompletion($owner, $admin);
        Notification::assertNothingSent();
    }
}
