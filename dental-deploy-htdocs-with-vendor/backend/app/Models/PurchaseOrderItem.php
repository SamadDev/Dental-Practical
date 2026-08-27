<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id', 'inventory_item_id', 'quantity_ordered',
        'quantity_received', 'unit_cost',
    ];

    protected $casts = [
        'quantity_ordered'  => 'integer',
        'quantity_received' => 'integer',
        'unit_cost'         => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}