<?php

namespace App\Enums;

enum TableStatus: string
{
    case FREE = 'free';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case CLEANING = 'cleaning';
    case OUT_OF_SERVICE = 'out_of_service';
}
