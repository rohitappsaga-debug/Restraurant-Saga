<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DeliveryDetail extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id', 'customer_name', 'customer_phone', 'address', 
        'driver_id', 'delivery_status'
    ];

    public function order() { return $this->belongsTo(Order::class, 'order_id'); }
    public function driver() { return $this->belongsTo(User::class, 'driver_id'); }
}