<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AqsatContract extends Model
{
    protected $fillable = [
        'patient_id', 'treatment_name', 'total_amount',
        'remaining_balance', 'status',
    ];

    // Cast money columns to integer so PHP arithmetic never drifts to float.
    protected $casts = [
        'total_amount'      => 'integer',
        'remaining_balance' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
