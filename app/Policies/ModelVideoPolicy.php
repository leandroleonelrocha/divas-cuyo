<?php

namespace App\Policies;

use App\Models\ModelVideo;
use App\Models\User;

class ModelVideoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVerifiedAdmin() || ($user->hasVerifiedEmail() && $user->modelProfile()->exists());
    }

    public function view(User $user, ModelVideo $video): bool
    {
        return $user->isVerifiedAdmin() || $this->delete($user, $video);
    }

    public function delete(User $user, ModelVideo $video): bool
    {
        return $user->hasVerifiedEmail() && $video->model_profile_id === $user->modelProfile()->value('id');
    }

    public function moderate(User $user, ModelVideo $video): bool
    {
        return $user->isVerifiedAdmin();
    }
}
