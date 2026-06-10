<?php

namespace App\Enums;

enum UserRole: string
{
    case WAITER = 'waiter';
    case ADMIN = 'admin';
    case KITCHEN = 'kitchen';
    case MANAGER = 'manager';
    case DELIVERY = 'delivery';
}
