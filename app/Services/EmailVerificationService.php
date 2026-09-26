<?php

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Notifications\VerifyModelEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailVerificationService
{
    public function send(User $user): void
    {
        $token = $this->issue($user);

        $user->notify(new VerifyModelEmail($token));
    }

    public function issue(User $user): string
    {
        return DB::transaction(function () use ($user): string {
            $user->emailVerificationToken()->delete();

            $token = Str::random(64);

            EmailVerificationToken::query()->create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(24),
            ]);

            return $token;
        });
    }

    public function verify(string $token): ?User
    {
        return DB::transaction(function () use ($token): ?User {
            $verification = EmailVerificationToken::query()
                ->where('token_hash', hash('sha256', $token))
                ->first();

            if (! $verification || $verification->expires_at->isPast()) {
                $verification?->delete();

                return null;
            }

            $user = User::query()->lockForUpdate()->find($verification->user_id);

            if (! $user) {
                $verification->delete();

                return null;
            }

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            $verification->delete();

            return $user;
        });
    }
}
