<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case CHECKED_IN = 'checked_in';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}
