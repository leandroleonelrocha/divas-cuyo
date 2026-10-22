<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountEnrollmentState
{
    public const INCOMPLETE = 'profile_incomplete';

    public const PENDING = 'pending_review';

    public const ACTIVE = 'active';

    public static function labels(): array
    {
        return [self::INCOMPLETE => 'Perfil incompleto', self::PENDING => 'Inscripción en revisión', self::ACTIVE => 'Inscripción habilitada'];
    }

    public function requireCompletion(User $owner, User $admin): void
    {
        $this->change($owner, $admin, self::INCOMPLETE);
    }

    public function finishReview(User $owner, User $admin): void
    {
        $this->change($owner, $admin, self::ACTIVE);
    }

    private function change(User $owner, User $admin, string $status): void
    {
        abort_unless($admin->fresh()?->isVerifiedAdmin(), 403);

        DB::transaction(function () use ($owner, $status): void {
            $locked = User::query()->lockForUpdate()->findOrFail($owner->id);
            abort_if($locked->isAdmin(), 403);
            if ($status === self::ACTIVE && ($locked->profile_completion_status !== self::PENDING
                || ! app(ProfileCompletionService::class)->isComplete($locked))) {
                throw ValidationException::withMessages(['profile_completion_status' => 'La inscripción debe estar completa y pendiente de revisión.']);
            }
            $locked->forceFill(['profile_completion_status' => $status])->save();
        });
    }
}
