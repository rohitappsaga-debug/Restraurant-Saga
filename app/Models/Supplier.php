<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Supplier extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'address'];

    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class, 'supplier_id'); }
}