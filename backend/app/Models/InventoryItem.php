<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'vendor_id', 'name', 'sku', 'category', 'unit',
        'unit_cost', 'sale_price', 'quantity_on_hand',
        'reorder_level', 'reorder_quantity', 'location',
        'expiry_date', 'track_expiry', 'is_active', 'notes',
    ];

    protected $casts = [
        'unit_cost'        => 'integer',
        'sale_price'       => 'integer',
        'quantity_on_hand' => 'integer',
        'reorder_level'    => 'integer',
        'reorder_quantity' => 'integer',
        'expiry_date'      => 'date',
        'track_expiry'     => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->track_expiry || !$this->expiry_date) return false;
        return $this->expiry_date <= now()->addDays(30)->toDateString();
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->track_expiry || !$this->expiry_date) return false;
        return $this->expiry_date < now()->toDateString();
    }

    public function scopeLowStock($q) { return $q->whereRaw('quantity_on_hand <= reorder_level'); }
    public function scopeExpiringSoon($q) { return $q->where('track_expiry', true)->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays(30)); }
    public function scopeExpired($q) { return $q->where('track_expiry', true)->whereNotNull('expiry_date')->where('expiry_date', '<', now()); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}