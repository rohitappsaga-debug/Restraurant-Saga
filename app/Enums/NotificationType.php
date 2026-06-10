<?php

namespace App\Enums;

enum NotificationType: string
{
    case ORDER = 'order';
    case PAYMENT = 'payment';
    case ALERT = 'alert';
}
