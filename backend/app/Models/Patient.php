<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'name', 'patient_code', 'gender', 'phone', 'age', 'appointment_date', 'is_smoker', 'medical_notes',
    ];

    protected $casts = [
        'is_smoker'        => 'boolean',
        'age'              => 'integer',
        'appointment_date' => 'string',
    ];

    protected static function booted(): void
    {
        // Every patient gets a short unique public code (PT-XXXXXX) used on
        // charts and for quick lookup at the reception desk.
        static::creating(function (self $patient) {
            if (empty($patient->patient_code)) {
                $patient->patient_code = self::generatePatientCode();
            }
        });
    }

    public static function generatePatientCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $code = 'PT';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::where('patient_code', $code)->exists());

        return $code;
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PatientCondition::class);
    }

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
