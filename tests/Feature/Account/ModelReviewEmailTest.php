<?php

namespace Tests\Feature\Account;

use App\Enums\ModelNotificationType;
use App\Models\User;
use App\Notifications\ModelReviewNotification;
use App\Services\ModelNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ModelReviewEmailTest extends TestCase
{
    public static function reviewTypes(): array
    {
        return array_map(fn ($type) => [$type], ModelNotificationType::cases());
    }

    #[DataProvider('reviewTypes')]
    public function test_each_review_notifies_only_the_owner_and_keeps_the_account_notice(ModelNotificationType $type): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $detail = $type->isRejection() ? 'La información no es legible.' : null;

        $notice = app(ModelNotificationService::class)->record($owner->modelProfile, $type, $detail);

        $this->assertDatabaseHas('model_notifications', ['id' => $notice->id, 'type' => $type->value]);
        Notification::assertSentTo($owner, ModelReviewNotification::class, function ($notification, $channels) use ($type, $detail, $owner) {
            $this->assertSame(['mail'], $channels);
            $mail = $notification->toMail($owner);
            $this->assertSame($type->label().' · Divas Cuyo', $mail->subject);
            $this->assertContains($type->message($detail), $mail->introLines);
            $this->assertSame(route('account.dashboard'), $mail->actionUrl);
            $this->assertStringContainsString($type->label(), (string) $mail->render());

            return $notification->type === $type && $notification->detail === $detail;
        });
        Notification::assertNotSentTo($other, ModelReviewNotification::class);
        Notification::assertCount(1);

        app(ModelNotificationService::class)->markAllRead($owner->modelProfile);
        Notification::assertCount(1);
    }

    public function test_email_is_queued_only_after_a_successful_transaction(): void
    {
        config(['queue.default' => 'database']);
        $owner = User::factory()->create();

        DB::transaction(function () use ($owner) {
            app(ModelNotificationService::class)->record($owner->modelProfile, ModelNotificationType::IdentityApproved);
            $this->assertDatabaseCount('jobs', 0);
        });

        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_rolled_back_review_does_not_queue_an_email_or_keep_a_notice(): void
    {
        config(['queue.default' => 'database']);
        $owner = User::factory()->create();

        try {
            DB::transaction(function () use ($owner) {
                app(ModelNotificationService::class)->record($owner->modelProfile, ModelNotificationType::ProfileApproved);
                throw new RuntimeException('Review failed');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Review failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('model_notifications', 0);
    }
}
