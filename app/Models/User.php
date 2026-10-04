<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function hasAdminAccess(): bool
    {
        return $this->is_active && in_array($this->adminRole(), ['admin', 'staff'], true);
    }

    public function adminRole(): string
    {
        // Preserve access for legacy admin records created before roles existed.
        return $this->is_admin ? 'admin' : (in_array($this->role, ['admin', 'staff'], true) ? $this->role : '');
    }

    public function canAccessAdminSection(string $section): bool
    {
        if (!$this->hasAdminAccess()) {
            return false;
        }

        if ($this->adminRole() === 'admin') {
            return true;
        }

        return in_array($section, ['dashboard', 'customers', 'applications', 'dana_tunai', 'chat', 'notifications'], true);
    }

    public function adminNotifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class, 'recipient_admin_id');
    }

    public function adminActivityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class, 'admin_id');
    }
}
