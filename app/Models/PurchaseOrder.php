<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchaseOrder extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['supplier_id', 'status', 'total_cost'];

    protected function casts(): array
    {
        return ['total_cost' => 'decimal:2'];
    }

    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id'); }
}