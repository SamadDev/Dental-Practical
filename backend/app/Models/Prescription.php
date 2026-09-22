<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A medicine the doctor prescribed to a patient. Stored as one row per drug
 * (rather than a text blob) so the chart can show who prescribed what and
 * when, and so an individual line can be removed if it was a mistake.
 */
class Prescription extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'visit_id', 'created_by',
        'medication', 'dose', 'frequency', 'duration', 'instructions', 'notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** The user account that typed the prescription. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user account shown in the patient record ("Issued by").
     * The dialog reads `prescriber.name`, so this is the relation the API
     * eager-loads instead of exposing the raw author id.
     */
    public function prescriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
