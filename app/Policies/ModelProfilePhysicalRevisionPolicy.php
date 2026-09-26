<?php

namespace App\Policies;

use App\Models\ModelProfilePhysicalRevision;
use App\Models\User;

class ModelProfilePhysicalRevisionPolicy
{
    public function view(User $user, ModelProfilePhysicalRevision $revision): bool
    {
        return $user->isVerifiedAdmin()
            || $revision->modelProfile?->user_id === $user->getKey();
    }

    public function approve(User $user, ModelProfilePhysicalRevision $revision): bool
    {
        return $user->isVerifiedAdmin() && $revision->status === 'pending';
    }

    public function reject(User $user, ModelProfilePhysicalRevision $revision): bool
    {
        return $user->isVerifiedAdmin() && $revision->status === 'pending';
    }
}
