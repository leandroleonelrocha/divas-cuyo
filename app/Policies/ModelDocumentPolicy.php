<?php

namespace App\Policies;

use App\Models\ModelDocument;
use App\Models\ModelProfile;
use App\Models\User;

class ModelDocumentPolicy
{
    public function view(User $user, ModelDocument $document): bool
    {
        return $this->isVerifiedAdmin($user) || $this->owns($user, $document);
    }

    public function download(User $user, ModelDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user, ModelProfile $profile): bool
    {
        return $this->isVerifiedModelOwner($user, $profile);
    }

    public function update(User $user, ModelDocument $document): bool
    {
        return $this->owns($user, $document);
    }

    public function delete(User $user, ModelDocument $document): bool
    {
        return $this->owns($user, $document);
    }

    private function owns(User $user, ModelDocument $document): bool
    {
        return $document->modelProfile?->user_id === $user->getKey();
    }

    private function isVerifiedModelOwner(User $user, ModelProfile $profile): bool
    {
        return $user->hasVerifiedEmail() && $profile->user?->is($user) === true;
    }

    private function isVerifiedAdmin(User $user): bool
    {
        return $user->isVerifiedAdmin();
    }
}
