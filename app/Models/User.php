<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_active', 'department_id', 'office_id', 'employee_id', 'phone',
        'last_login_at', 'mfa_secret', 'mfa_enabled', 'must_change_password',
        'password_changed_at', 'failed_login_attempts', 'locked_until',
    ];

    protected $hidden = ['password', 'remember_token', 'mfa_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean',
            'last_login_at' => 'datetime', 'mfa_secret' => 'encrypted', 'mfa_enabled' => 'boolean',
            'must_change_password' => 'boolean', 'password_changed_at' => 'datetime',
            'locked_until' => 'datetime', 'failed_login_attempts' => 'integer',
        ];
    }

    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class); }
    public function approvals() { return $this->hasMany(Approval::class, 'approver_id'); }
    public function employee() { return $this->belongsTo(Employee::class, 'employee_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function office() { return $this->belongsTo(Office::class); }
    public function loginHistories() { return $this->hasMany(LoginHistory::class); }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || ($this->locked_until && $this->locked_until->isFuture())) {
            return false;
        }

        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('name', $permission))->exists()
            || $this->roles()->where('slug', 'super-admin')->exists();
    }
}
