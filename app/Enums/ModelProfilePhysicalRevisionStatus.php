<?php

namespace App\Enums;

enum ModelProfilePhysicalRevisionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
