<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Setting extends Model
{
    use HasUuids;

    public static function current()
    {
        $attributes = cache()->rememberForever('global_settings_attributes', function () {
            return self::first()?->getAttributes();
        });

        return $attributes ? (new self())->setRawAttributes($attributes, true) : null;
    }

    protected static function booted()
    {
        static::saved(function () {
            cache()->forget('global_settings_attributes');
        });
        static::deleted(function () {
            cache()->forget('global_settings_attributes');
        });
    }

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