<?php

namespace App\Policies;

use App\Models\ModelProfilePublicationTypeHistory;
use App\Models\User;

class ModelProfilePublicationTypeHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVerifiedAdmin();
    }

    public function view(User $user, ModelProfilePublicationTypeHistory $history): bool
    {
        return $user->isVerifiedAdmin();
    }
}
