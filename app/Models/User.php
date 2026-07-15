<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\UserRole;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'active', 'theme', 'notifications_enabled'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
            'notifications_enabled' => 'boolean',
        ];
    }

    public function activityLogs() { return $this->hasMany(ActivityLog::class, 'user_id'); }
    public function notifications() { return $this->hasMany(Notification::class, 'user_id'); }
    public function createdOrders() { return $this->hasMany(Order::class, 'created_by'); }
    public function deviceTokens() { return $this->hasMany(DeviceToken::class, 'user_id'); }
}