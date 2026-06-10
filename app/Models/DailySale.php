<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DailySale extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'date', 'total_sales', 'total_orders', 'average_order_value'
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_sales' => 'decimal:2',
            'average_order_value' => 'decimal:2',
            'total_orders' => 'integer'
        ];
    }
}