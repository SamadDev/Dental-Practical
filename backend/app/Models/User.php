<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /** Role helpers backed by Spatie. */
    public function isAdmin(): bool        { return $this->hasRole('admin'); }
    public function isDoctor(): bool       { return $this->hasRole('doctor'); }
    public function isReceptionist(): bool { return $this->hasRole('receptionist'); }
    public function isHygienist(): bool    { return $this->hasRole('hygienist'); }

    public function hasPermission(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    /** Spatie role + legacy column sync. */
    public function assignSyncRole(string $roleName): void
    {
        $this->syncRoles([$roleName]);
        $this->forceFill(['role' => $roleName])->saveQuietly();
    }

    // ── Doctor profile (one-to-one, for users with role=doctor) ────────────────

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    // ── Receptionist <-> Doctor assignments (many-to-many) ────────────────────

    /** Doctors this receptionist is assigned to. */
    public function assignedDoctors(): BelongsToMany
    {
        return $this->belongsToMany(
            Doctor::class,
            'receptionist_doctor',
            'receptionist_user_id',
            'doctor_id'
        )->withTimestamps();
    }

    /**
     * IDs of doctors this user can access. Admins see all; doctors/hygienists
     * see their own; receptionists see only assigned doctors.
     */
    public function accessibleDoctorIds(): array
    {
        if ($this->isAdmin()) {
            return Doctor::query()->pluck('id')->all();
        }

        $ids = [];

        if (($this->isDoctor() || $this->isHygienist()) && $this->doctorProfile) {
            $ids[] = $this->doctorProfile->id;
        }

        if ($this->isReceptionist()) {
            $ids = array_merge($ids, $this->assignedDoctors()->pluck('id')->all());
        }

        return array_values(array_unique($ids));
    }
}
