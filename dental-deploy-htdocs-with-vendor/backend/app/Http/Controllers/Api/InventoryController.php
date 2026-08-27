<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with('vendor');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereRaw('quantity_on_hand <= reorder_level');
        }

        if ($request->boolean('expiring')) {
            $query->where('track_expiry', true)
                  ->whereNotNull('expiry_date')
                  ->where('expiry_date', '<=', now()->addDays(30)->toDateString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $sort = $request->get('sort', 'name');
        $dir  = $request->get('dir', 'asc');
        $query->orderBy($sort, $dir);

        $perPage = $request->get('per_page', 25);
        return $query->paginate($perPage);
    }

    public function categories()
    {
        return InventoryItem::distinct()->pluck('category')->filter()->values();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'sku'               => 'required|string|max:100|unique:inventory_items,sku',
            'category'          => 'required|string|max:100',
            'unit'              => 'nullable|string|max:20',
            'unit_cost'         => 'required|integer|min:0',
            'quantity_on_hand'  => 'integer|min:0',
            'reorder_level'     => 'integer|min:0',
            'vendor_id'         => 'nullable|exists:vendors,id',
            'track_expiry'      => 'boolean',
            'expiry_date'       => 'nullable|date',
        ]);

        $item = InventoryItem::create($data);
        return response()->json($item, 201);
    }

    public function move(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'type'           => 'required|in:in,out,adjustment,waste',
            'quantity'       => 'required|integer|min:1',
            'batch_number'   => 'nullable|string|max:100',
        ]);

        $movement = InventoryMovement::create([
            'inventory_item_id' => $item->id,
            'type'              => $data['type'],
            'quantity'          => $data['quantity'],
            'batch_number'      => $data['batch_number'] ?? null,
        ]);

        // Update quantity_on_hand
        $adjustment = match ($data['type']) {
            'in'          => $data['quantity'],
            'out', 'waste'=> -$data['quantity'],
            'adjustment'  => $data['quantity'],
            default       => 0,
        };

        $item->increment('quantity_on_hand', $adjustment);

        return response()->json($movement->load('item'));
    }
}