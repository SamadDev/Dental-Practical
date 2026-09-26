<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only snapshot of a tooth's state after a change. */
class ToothRecordHistory extends Model
{
    /** Table keeps the singular name used by the migration. */
    protected $table = 'tooth_record_history';

    protected $fillable = [
        'patient_id', 'tooth_number', 'status', 'note', 'surfaces', 'visit_id',
    ];

    protected $casts = [
        'tooth_number' => 'integer',
        'surfaces'     => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
