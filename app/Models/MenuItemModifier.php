<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MenuItemModifier extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['menu_item_id', 'name', 'price', 'available'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'available' => 'boolean'
        ];
    }

    public function menuItem() { return $this->belongsTo(MenuItem::class, 'menu_item_id'); }
}