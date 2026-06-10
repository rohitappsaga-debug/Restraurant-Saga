<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Recipe extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['menu_item_id', 'ingredient_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }

    public function menuItem() { return $this->belongsTo(MenuItem::class, 'menu_item_id'); }
    public function ingredient() { return $this->belongsTo(Ingredient::class, 'ingredient_id'); }
}