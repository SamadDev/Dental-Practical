<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlan extends Model
{
    protected $fillable = [
        'patient_id', 'name', 'total_amount', 'down_payment',
        'installment_amount', 'installment_count', 'frequency_days', 'start_date',
    ];

    protected $casts = [
        'total_amount'       => 'integer',
        'down_payment'       => 'integer',
        'installment_amount' => 'integer',
        'installment_count'  => 'integer',
        'frequency_days'     => 'integer',
        'start_date'         => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class, 'payment_plan_id');
    }
}