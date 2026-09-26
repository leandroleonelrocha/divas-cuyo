<?php

namespace App\Enums;

enum ModelProfileBioStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
