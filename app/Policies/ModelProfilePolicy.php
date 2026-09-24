<?php

namespace App\Policies;

use App\Models\ModelProfile;
use App\Models\User;

class ModelProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function view(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function update(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user);
    }

    public function approve(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user)
            && $profile->identity_status === 'approved'
            && in_array($profile->review_status, ['pending', 'rejected'], true);
    }

    public function reject(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user)
            && in_array($profile->review_status, ['pending', 'approved'], true);
    }

    public function approveIdentity(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user) && $profile->identity_status === 'pending';
    }

    public function rejectIdentity(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user) && $profile->identity_status === 'pending';
    }

    public function publish(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user)
            && $profile->identity_status === 'approved'
            && $profile->review_status === 'approved'
            && ! $profile->is_published;
    }

    public function unpublish(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedAdmin($user) && $profile->is_published;
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->isVerifiedAdmin();
    }
}
