<?php

namespace App\Policies;

use App\Models\ModelProfileBio;
use App\Models\User;

class ModelProfileBioPolicy
{
    public function view(User $user, ModelProfileBio $bio): bool
    {
        return $user->isVerifiedAdmin() || (int) $bio->modelProfile?->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->modelProfile()->exists();
    }

    public function update(User $user, ModelProfileBio $bio): bool
    {
        return $user->hasVerifiedEmail()
            && (int) $bio->modelProfile?->user_id === (int) $user->getKey()
            && $bio->status === 'rejected';
    }

    public function approve(User $user, ModelProfileBio $bio): bool
    {
        return $user->isVerifiedAdmin() && $bio->status === 'pending';
    }

    public function reject(User $user, ModelProfileBio $bio): bool
    {
        return $user->isVerifiedAdmin() && $bio->status === 'pending';
    }
}
