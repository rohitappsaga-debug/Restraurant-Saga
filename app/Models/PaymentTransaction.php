<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\PaymentMethod;

class PaymentTransaction extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['order_id', 'amount', 'method', 'status', 'transaction_id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class
        ];
    }

    public function order() { return $this->belongsTo(Order::class, 'order_id'); }
}