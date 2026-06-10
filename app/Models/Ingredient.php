<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ingredient extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'unit', 'stock', 'min_level'];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
            'min_level' => 'decimal:3'
        ];
    }

    public function purchaseOrderItems() { return $this->hasMany(PurchaseOrderItem::class, 'ingredient_id'); }
    public function recipes() { return $this->hasMany(Recipe::class, 'ingredient_id'); }
}