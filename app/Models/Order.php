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
        'session_id', 'table_number', 'status', 'created_by', 'total', 'type',
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
    public function session() { return $this->belongsTo(TableSession::class, 'session_id'); }
    public function kots() { return $this->hasMany(Kot::class, 'order_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function table() { return $this->belongsTo(Table::class, 'table_number', 'number'); }
    public function parentOrder() { return $this->belongsTo(Order::class, 'parent_order_id'); }
    public function childOrders() { return $this->hasMany(Order::class, 'parent_order_id'); }
    public function deliveryDetails() { return $this->hasOne(DeliveryDetail::class, 'order_id'); }
    public function paymentTransactions() { return $this->hasMany(PaymentTransaction::class, 'order_id'); }
}