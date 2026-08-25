<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $fillable = [
        'patient_id', 'aqsat_contract_id', 'queue_status', 'visit_type',
        'treatment_notes', 'xray_path',
        'total_cost', 'amount_paid', 'short_term_debt',
    ];

    protected $casts = [
        'total_cost'      => 'integer',
        'amount_paid'     => 'integer',
        'short_term_debt' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function aqsatContract(): BelongsTo
    {
        return $this->belongsTo(AqsatContract::class);
    }

    public function scopePending($q)    { return $q->where('queue_status', 'pending'); }
    public function scopeActive($q)     { return $q->where('queue_status', 'active'); }
    public function scopeCompleted($q)  { return $q->where('queue_status', 'completed'); }
}
