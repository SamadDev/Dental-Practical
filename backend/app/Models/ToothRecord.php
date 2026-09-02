<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToothRecord extends Model
{
    protected $fillable = ['patient_id', 'tooth_number', 'status', 'note'];

    protected $casts = ['tooth_number' => 'integer'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
