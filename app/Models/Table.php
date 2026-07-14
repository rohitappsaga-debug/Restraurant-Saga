<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\TableStatus;

class Table extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'number', 'capacity', 'status', 'current_order_id', 'reserved_by', 
        'reserved_time', 'group_id', 'is_primary'
    ];

    protected function casts(): array
    {
        return [
            'status' => TableStatus::class,
            'reserved_time' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    public function orders() { return $this->hasMany(Order::class, 'table_number', 'number'); }
    public function reservations() { return $this->hasMany(Reservation::class, 'table_number', 'number'); }
    public function currentOrder() { return $this->belongsTo(Order::class, 'current_order_id'); }
    public function allOrders() { return $this->belongsToMany(Order::class, 'order_table')->withTimestamps(); }
}