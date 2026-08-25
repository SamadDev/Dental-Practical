<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_item_id', 'type', 'quantity', 'unit_cost_at_time',
        'reference_id', 'reference_type', 'batch_number', 'expiry_date',
        'notes', 'user_id',
    ];

    protected $casts = [
        'quantity'            => 'integer',
        'unit_cost_at_time'   => 'integer',
        'expiry_date'         => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}