<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'inventory_item_id', 'quantity_ordered',
        'quantity_received', 'unit_cost', 'line_total', 'notes',
    ];

    protected $casts = [
        'quantity_ordered'  => 'integer',
        'quantity_received' => 'integer',
        'unit_cost'         => 'integer',
        'line_total'        => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function getRemainingAttribute(): int
    {
        return $this->quantity_ordered - $this->quantity_received;
    }
}