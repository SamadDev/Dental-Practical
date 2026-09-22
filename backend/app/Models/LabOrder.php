<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Work sent out of the clinic, following one hand-off for both kinds:
 *   pending -> in_lab -> ready -> delivered      (or cancelled)
 *
 *   prosthetic : crown / bridge / denture / implant made by a dental lab
 *   diagnostic : blood test / X-ray / culture requested by the doctor
 *
 * The doctor writes the request, the lab sees it immediately and can print
 * the sheet, then records the result and sends it back.
 */
class LabOrder extends Model
{
    public const CATEGORIES = ['prosthetic', 'diagnostic'];

    public const STATUSES = ['pending', 'in_lab', 'ready', 'delivered', 'cancelled'];

    protected $fillable = [
        'order_number', 'patient_id', 'doctor_id', 'visit_id', 'created_by',
        'category', 'lab_type', 'test_name', 'tooth_number', 'material', 'shade',
        'assigned_lab', 'due_date', 'cost', 'notes',
        'status', 'result_notes', 'result_file_path',
        'requested_at', 'completed_at',
    ];

    protected $casts = [
        'cost'         => 'integer',
        'due_date'     => 'date:Y-m-d',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // LAB-000123 — sequential, and what the printed request sheet shows.
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        do {
            $next = (int) self::max('id') + 1;
            $code = 'LAB-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        } while (self::where('order_number', $code)->exists());

        return $code;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** The doctor who requested the work. */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($q)   { return $q->where('status', 'pending'); }
    public function scopeOpen($q)      { return $q->whereNotIn('status', ['delivered', 'cancelled']); }
    public function scopeDiagnostic($q){ return $q->where('category', 'diagnostic'); }
    public function scopeProsthetic($q){ return $q->where('category', 'prosthetic'); }

    /** True once the lab has recorded something the doctor can read. */
    public function hasResult(): bool
    {
        return filled($this->result_notes) || filled($this->result_file_path);
    }
}
