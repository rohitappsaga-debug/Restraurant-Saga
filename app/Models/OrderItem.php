<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\OrderStatus;

class OrderItem extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id', 'menu_item_id', 'kot_id', 'quantity', 'notes', 'status', 'modifiers',
        'served_at', 'served_by', 'cancelled_at', 'cancelled_by', 'cancel_reason'
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'modifiers' => 'array',
            'served_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order() { return $this->belongsTo(Order::class, 'order_id'); }
    public function kot() { return $this->belongsTo(Kot::class, 'kot_id'); }
    public function menuItem() { return $this->belongsTo(MenuItem::class, 'menu_item_id'); }
    public function server() { return $this->belongsTo(User::class, 'served_by'); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }
}