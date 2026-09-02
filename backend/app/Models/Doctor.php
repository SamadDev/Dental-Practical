<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $fillable = [
        'user_id', 'specialty', 'phone', 'color', 'bio', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /** Receptionists assigned to this doctor */
    public function receptionists(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'receptionist_doctor',
            'doctor_id',
            'receptionist_user_id'
        )->withTimestamps();
    }

    // ── Accessors ───────────────────────────────────────────────────────────────

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown Doctor';
    }

    public function getEmailAttribute(): string
    {
        return $this->user?->email ?? '';
    }

    public function getIsUserActiveAttribute(): bool
    {
        return $this->user?->is_active ?? false;
    }
}
