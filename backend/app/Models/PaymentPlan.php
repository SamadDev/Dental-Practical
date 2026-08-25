<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPlan extends Model
{
    protected $fillable = [
        'patient_id', 'visit_id', 'name', 'total_amount',
        'down_payment', 'installment_amount', 'frequency_days',
        'installment_count', 'start_date', 'status', 'notes',
    ];

    protected $casts = [
        'total_amount'      => 'integer',
        'down_payment'      => 'integer',
        'installment_amount'=> 'integer',
        'frequency_days'    => 'integer',
        'installment_count' => 'integer',
        'start_date'        => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class)->orderBy('installment_number');
    }

    public function getPaidInstallmentsAttribute(): int
    {
        return $this->installments()->where('status', 'paid')->count();
    }

    public function getRemainingBalanceAttribute(): int
    {
        return $this->installments()->whereIn('status', ['pending', 'partial', 'overdue'])->sum('amount');
    }

    public function getNextDueDateAttribute(): ?string
    {
        $next = $this->installments()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->first();
        return $next?->due_date;
    }
}