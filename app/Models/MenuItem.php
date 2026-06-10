<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MenuItem extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'category', 'price', 'description', 'image_url', 'image', 'available', 
        'preparation_time', 'category_id', 'available_from', 'available_to', 
        'is_veg', 'availability_reason'
    ];

    public function getThumbnailUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return $this->image_url;
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'available' => 'boolean',
            'is_veg' => 'boolean',
            'preparation_time' => 'integer',
        ];
    }

    public function categoryInfo() { return $this->belongsTo(Category::class, 'category_id'); }
    public function modifiers() { return $this->hasMany(MenuItemModifier::class, 'menu_item_id'); }
    public function orderItems() { return $this->hasMany(OrderItem::class, 'menu_item_id'); }
    public function recipes() { return $this->hasMany(Recipe::class, 'menu_item_id'); }
}