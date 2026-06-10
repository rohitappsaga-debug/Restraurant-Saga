<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityLog extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    
    // Explicit table name mapping to match Prisma
    protected $table = 'activity_logs';

    protected $fillable = ['user_id', 'action', 'details'];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
}