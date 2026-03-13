<?php

namespace App\Enums;

enum NotificationType: string
{
    case Transaction = 'transaction';
    case Promo = 'promo';
    case Security = 'security';
    case System = 'system';
}
