<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Setting extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tax_rate', 'currency', 'restaurant_name', 'discount_presets', 
        'printer_config', 'business_hours', 'enabled_payment_methods', 
        'receipt_footer', 'gst_no', 'restaurant_address', 'tax_enabled', 
        'notification_preferences', 'reservation_grace_period'
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'discount_presets' => 'array',
            'printer_config' => 'array',
            'business_hours' => 'array',
            'enabled_payment_methods' => 'array',
            'notification_preferences' => 'array',
            'tax_enabled' => 'boolean',
            'reservation_grace_period' => 'integer',
        ];
    }
}