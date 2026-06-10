<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\ReservationStatus;

class Reservation extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'table_number', 'customer_name', 'customer_phone', 'date', 
        'start_time', 'end_time', 'status'
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => ReservationStatus::class,
        ];
    }

    public function table() { return $this->belongsTo(Table::class, 'table_number', 'number'); }
}