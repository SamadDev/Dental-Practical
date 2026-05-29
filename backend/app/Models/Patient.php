<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'name', 'phone', 'age', 'appointment_date', 'is_smoker', 'medical_notes',
    ];

    protected $casts = [
        'is_smoker'        => 'boolean',
        'age'              => 'integer',
        'appointment_date' => 'string',
    ];

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function aqsatContracts(): HasMany
    {
        return $this->hasMany(AqsatContract::class);
    }

    /** Sum of short_term_debt across all visits — quick pull on next appointment. */
    public function getOutstandingShortTermDebtAttribute(): int
    {
        return (int) $this->visits()->sum('short_term_debt');
    }
}
