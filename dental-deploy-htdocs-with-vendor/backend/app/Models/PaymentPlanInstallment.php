<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanInstallment extends Model
{
    protected $table = 'payment_plan_installments';

    protected $fillable = [
        'payment_plan_id', 'installment_number', 'amount', 'amount_paid',
        'due_date', 'paid_date', 'status',
    ];

    protected $casts = [
        'amount'         => 'integer',
        'amount_paid'    => 'integer',
        'installment_number' => 'integer',
        'due_date'       => 'date',
        'paid_date'      => 'date',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class, 'payment_plan_id');
    }
}