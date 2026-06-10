<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case SERVED = 'served';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
