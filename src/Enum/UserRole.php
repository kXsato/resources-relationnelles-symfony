<?php

namespace App\Enum;

enum UserRole: string
{
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_MODERATOR = 'ROLE_MODERATOR';
}