<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;

class Order extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'table_number', 'status', 'created_by', 'total', 'type',
        'discount_type', 'discount_value', 'is_paid', 
        'payment_method', 'service_charge', 'cancel_reason', 'hold_status', 'parent_order_id'
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'is_paid' => 'boolean',
            'hold_status' => 'boolean',
        ];
    }

    public function orderItems() { return $this->hasMany(OrderItem::class, 'order_id'); }
    public function tables() { return $this->belongsToMany(Table::class, 'order_table')->withTimestamps(); }

    // "5+6" style label spanning all attached tables; table_number only holds the primary table
    public function getTableLabelAttribute(): string
    {
        $numbers = $this->tables->pluck('number')->sort()->values();

        return $numbers->isNotEmpty() ? $numbers->implode('+') : (string) $this->table_number;
    }

    public function scopeOpen($query)
    {
        return $query->where('is_paid', false)->where('status', '!=', OrderStatus::CANCELLED);
    }
    public function kots() { return $this->hasMany(Kot::class, 'order_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function table() { return $this->belongsTo(Table::class, 'table_number', 'number'); }
    public function parentOrder() { return $this->belongsTo(Order::class, 'parent_order_id'); }
    public function childOrders() { return $this->hasMany(Order::class, 'parent_order_id'); }
    public function deliveryDetails() { return $this->hasOne(DeliveryDetail::class, 'order_id'); }
    public function paymentTransactions() { return $this->hasMany(PaymentTransaction::class, 'order_id'); }
}