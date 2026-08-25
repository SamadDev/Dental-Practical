<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'name', 'contact_person', 'phone', 'email', 'address',
        'tax_number', 'payment_terms_days', 'is_active', 'notes',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'is_active'          => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}