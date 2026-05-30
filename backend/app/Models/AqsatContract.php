<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AqsatContract extends Model
{
    protected $fillable = [
        'patient_id', 'treatment_name', 'total_amount',
        'installment_amount', 'remaining_balance', 'status',
    ];

    // Cast money columns to integer so PHP arithmetic never drifts to float.
    protected $casts = [
        'total_amount'       => 'integer',
        'installment_amount' => 'integer',
        'remaining_balance'  => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    // --- Derived aggregates exposed to the frontend ----------------------

    /** Total cash collected so far against this contract. */
    public function getPaidAmountAttribute(): int
    {
        return (int) $this->total_amount - (int) $this->remaining_balance;
    }

    /** Expected total number of installments (ceil to cover any non-divisible remainder). */
    public function getTotalInstallmentsAttribute(): int
    {
        $per = (int) $this->installment_amount;
        if ($per <= 0) return 0;
        return (int) ceil($this->total_amount / $per);
    }

    /** How many full installments have been paid (integer division). */
    public function getPaidInstallmentsAttribute(): int
    {
        $per = (int) $this->installment_amount;
        if ($per <= 0) return 0;
        return intdiv($this->paid_amount, $per);
    }

    public function getRemainingInstallmentsAttribute(): int
    {
        return max(0, $this->total_installments - $this->paid_installments);
    }

    /**
     * Monthly cadence: next installment is one month after the most recent
     * payment, or one month after contract creation if nothing has been paid.
     * Returns null once the contract is fully paid.
     */
    public function getNextDueDateAttribute(): ?string
    {
        if ($this->remaining_balance <= 0) return null;

        $last = $this->visits()
            ->where('amount_paid', '>', 0)
            ->orderByDesc('created_at')
            ->value('created_at');

        $base = $last ? \Illuminate\Support\Carbon::parse($last) : $this->created_at;
        return $base?->copy()->addMonth()->toDateString();
    }

    /**
     * Project the final installment date assuming one installment per month
     * from today. Returns null once the contract is fully paid.
     */
    public function getExpectedCompletionDateAttribute(): ?string
    {
        $remaining = $this->remaining_installments;
        if ($remaining <= 0) return null;

        // Anchor on next_due_date (already monthly-based) and add the remaining
        // installments minus one — that's the date of the FINAL payment.
        $next = $this->next_due_date;
        if (!$next) return null;
        return \Illuminate\Support\Carbon::parse($next)
            ->addMonths($remaining - 1)
            ->toDateString();
    }
}
