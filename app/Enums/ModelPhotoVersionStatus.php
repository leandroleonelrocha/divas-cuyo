<?php

namespace App\Enums;

enum ModelPhotoVersionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
