<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\NotificationType;

class Notification extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['type', 'message', 'user_id', 'read'];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'read' => 'boolean',
        ];
    }

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}