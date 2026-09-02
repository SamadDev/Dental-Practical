<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'role'              => 'string',
        'is_active'         => 'boolean',
    ];

    /**
     * Legacy single-role column kept in sync with the Spatie role
     * (assigned in RolesAndPermissionsSeeder and on user create/update).
     */
    public function isAdmin(): bool       { return $this->hasRole('admin'); }
    public function isDoctor(): bool      { return $this->hasRole('doctor'); }
    public function isReceptionist(): bool { return $this->hasRole('receptionist'); }
    public function isHygienist(): bool   { return $this->hasRole('hygienist'); }

    /** Spatie-backed permission check (roles + direct permissions). */
    public function hasPermission(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    /**
     * Assign a Spatie role and keep the legacy `role` column in sync
     * (the column predates the package and is still read by older code).
     */
    public function assignSyncRole(string $roleName): void
    {
        $this->syncRoles([$roleName]);
        $this->forceFill(['role' => $roleName])->saveQuietly();
    }
}