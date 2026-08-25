<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanInstallment extends Model
{
    protected $fillable = [
        'payment_plan_id', 'installment_number', 'due_date',
        'amount', 'amount_paid', 'status', 'paid_date', 'notes',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'amount'             => 'integer',
        'amount_paid'        => 'integer',
        'due_date'           => 'date',
        'paid_date'          => 'date',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class, 'payment_plan_id');
    }

    public function getRemainingAttribute(): int
    {
        return $this->amount - $this->amount_paid;
    }

    public function scopePending($q)    { return $q->where('status', 'pending'); }
    public function scopeOverdue($q)    { return $q->where('status', 'overdue'); }
    public function scopeDueSoon($q, int $days = 7) {
        return $q->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<=', now()->addDays($days)->toDateString());
    }
}