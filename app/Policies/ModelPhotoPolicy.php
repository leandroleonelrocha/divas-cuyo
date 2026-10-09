<?php

namespace App\Policies;

use App\Models\ModelPhoto;
use App\Models\ModelProfile;
use App\Models\User;

class ModelPhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVerifiedAdmin()
            || ($user->hasVerifiedEmail() && $user->modelProfile()->exists());
    }

    public function view(User $user, ModelPhoto $photo): bool
    {
        return $user->hasVerifiedEmail() && $this->owns($user, $photo);
    }

    public function create(User $user, ModelProfile $profile): bool
    {
        return $user->hasVerifiedEmail() && $profile->user_id === $user->getKey();
    }

    public function reorder(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedOwner($user, $profile);
    }

    public function setPrimary(User $user, ModelPhoto $photo): bool
    {
        return $user->hasVerifiedEmail() && $this->owns($user, $photo);
    }

    public function replace(User $user, ModelPhoto $photo): bool
    {
        return $this->setPrimary($user, $photo);
    }

    public function delete(User $user, ModelPhoto $photo): bool
    {
        return $this->setPrimary($user, $photo);
    }

    public function moderate(User $user, ModelPhoto $photo): bool
    {
        return $user->isVerifiedAdmin() && $photo->modelProfile()->exists();
    }

    public function approve(User $user, ModelPhoto $photo): bool
    {
        return $this->moderate($user, $photo);
    }

    public function reject(User $user, ModelPhoto $photo): bool
    {
        return $this->moderate($user, $photo);
    }

    public function deliverPrivate(User $user, ModelPhoto $photo): bool
    {
        return $this->moderate($user, $photo);
    }

    private function owns(User $user, ModelPhoto $photo): bool
    {
        return $photo->model_profile_id === $user->modelProfile()->value('id');
    }

    private function isVerifiedOwner(User $user, ModelProfile $profile): bool
    {
        return $user->hasVerifiedEmail() && $profile->user_id === $user->getKey();
    }
}
