<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

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

    public function isAdmin(): bool       { return $this->role === 'admin'; }
    public function isDoctor(): bool      { return $this->role === 'doctor'; }
    public function isReceptionist(): bool { return $this->role === 'receptionist'; }
    public function isHygienist(): bool   { return $this->role === 'hygienist'; }

    public function hasPermission(string $permission): bool
    {
        return match ($this->role) {
            'admin'        => true,
            'doctor'       => in_array($permission, $this->doctorPermissions()),
            'receptionist' => in_array($permission, $this->receptionistPermissions()),
            'hygienist'    => in_array($permission, $this->hygienistPermissions()),
            default        => false,
        };
    }

    private function doctorPermissions(): array
    {
        return [
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'queue.view', 'queue.manage',
            'visits.view', 'visits.create', 'visits.edit', 'visits.checkout', 'visits.xray',
            'archive.view',
            'aqsat.view', 'aqsat.create', 'aqsat.edit',
            'payment_plans.view', 'payment_plans.create', 'payment_plans.edit',
            'inventory.view',
            'dashboard.view',
        ];
    }

    private function receptionistPermissions(): array
    {
        return [
            'patients.view', 'patients.create', 'patients.edit',
            'queue.view', 'queue.manage',
            'visits.view', 'visits.create', 'visits.checkout',
            'archive.view',
            'aqsat.view', 'aqsat.create', 'aqsat.edit',
            'payment_plans.view', 'payment_plans.create', 'payment_plans.edit',
            'expenses.view', 'expenses.create', 'expenses.delete',
            'inventory.view', 'inventory.move',
            'dashboard.view',
        ];
    }

    private function hygienistPermissions(): array
    {
        return [
            'patients.view',
            'queue.view',
            'visits.view', 'visits.create', 'visits.edit', 'visits.xray',
            'archive.view',
            'inventory.view',
        ];
    }
}