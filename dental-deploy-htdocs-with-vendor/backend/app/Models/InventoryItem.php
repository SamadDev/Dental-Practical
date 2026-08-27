<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $fillable = [
        'name', 'sku', 'category', 'unit', 'unit_cost',
        'quantity_on_hand', 'reorder_level', 'vendor_id',
        'track_expiry', 'expiry_date',
    ];

    protected $casts = [
        'unit_cost'        => 'integer',
        'quantity_on_hand' => 'integer',
        'reorder_level'    => 'integer',
        'track_expiry'     => 'boolean',
        'expiry_date'      => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_item_id');
    }
}