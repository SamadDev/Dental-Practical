<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = InventoryItem::with('vendor:id,name')
            ->when($request->query('category'), fn ($qq, $c) => $qq->where('category', $c))
            ->when($request->boolean('low_stock'), fn ($qq) => $qq->lowStock())
            ->when($request->boolean('expiring'), fn ($qq) => $qq->expiringSoon())
            ->when($request->boolean('expired'), fn ($qq) => $qq->expired())
            ->when($s = $request->query('search'), fn ($qq) => $qq->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            }))
            ->when($request->boolean('active_only', true), fn ($qq) => $qq->active());

        $sort = $request->query('sort', 'name');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['name', 'sku', 'category', 'quantity_on_hand', 'unit_cost', 'reorder_level', 'expiry_date'];
        $q->orderBy(in_array($sort, $allowed) ? $sort : 'name', $dir);

        $perPage = max(5, min(200, (int) $request->query('per_page', 50) ?: 50));

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id'        => 'nullable|exists:vendors,id',
            'name'             => 'required|string|max:255',
            'sku'              => 'required|string|max:100|unique:inventory_items',
            'category'         => 'required|string|max:100',
            'unit'             => 'sometimes|string|max:20',
            'unit_cost'        => 'required|integer|min:0',
            'sale_price'       => 'nullable|integer|min:0',
            'quantity_on_hand' => 'sometimes|integer|min:0',
            'reorder_level'    => 'sometimes|integer|min:0',
            'reorder_quantity' => 'sometimes|integer|min:1',
            'location'         => 'nullable|string|max:100',
            'expiry_date'      => 'nullable|date',
            'track_expiry'     => 'sometimes|boolean',
            'is_active'        => 'sometimes|boolean',
            'notes'            => 'nullable|string',
        ]);

        $item = InventoryItem::create($data);

        // Record initial stock as movement
        if (($data['quantity_on_hand'] ?? 0) > 0) {
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type'              => 'in',
                'quantity'          => $data['quantity_on_hand'],
                'unit_cost_at_time' => $data['unit_cost'],
                'reference_type'    => 'manual',
                'notes'             => 'Initial stock',
            ]);
        }

        return response()->json($item->load('vendor'), 201);
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json($inventoryItem->load(['vendor', 'movements' => fn ($q) => $q->latest()->limit(20)]));
    }

    public function update(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $data = $request->validate([
            'vendor_id'        => 'nullable|exists:vendors,id',
            'name'             => 'sometimes|string|max:255',
            'sku'              => 'sometimes|string|max:100|unique:inventory_items,sku,' . $inventoryItem->id,
            'category'         => 'sometimes|string|max:100',
            'unit'             => 'sometimes|string|max:20',
            'unit_cost'        => 'sometimes|integer|min:0',
            'sale_price'       => 'nullable|integer|min:0',
            'reorder_level'    => 'sometimes|integer|min:0',
            'reorder_quantity' => 'sometimes|integer|min:1',
            'location'         => 'nullable|string|max:100',
            'expiry_date'      => 'nullable|date',
            'track_expiry'     => 'sometimes|boolean',
            'is_active'        => 'sometimes|boolean',
            'notes'            => 'nullable|string',
        ]);

        $inventoryItem->update($data);
        return response()->json($inventoryItem->load('vendor'));
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->delete();
        return response()->json(['ok' => true]);
    }

    /** Record stock movement (in/out/adjustment). */
    public function move(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $data = $request->validate([
            'type'                 => 'required|in:in,out,adjustment,transfer,waste,expired',
            'quantity'             => 'required|integer|min:1',
            'unit_cost_at_time'    => 'nullable|integer|min:0',
            'reference_id'         => 'nullable|integer',
            'reference_type'       => 'nullable|string|max:50',
            'batch_number'         => 'nullable|string|max:100',
            'expiry_date'          => 'nullable|date',
            'notes'                => 'nullable|string',
        ]);

        $qty = $data['type'] === 'in' ? $data['quantity'] : -$data['quantity'];

        return DB::transaction(function () use ($inventoryItem, $data, $qty) {
            $inventoryItem->increment('quantity_on_hand', $qty);

            $movement = InventoryMovement::create([
                'inventory_item_id'   => $inventoryItem->id,
                'type'                => $data['type'],
                'quantity'            => $qty,
                'unit_cost_at_time'   => $data['unit_cost_at_time'] ?? $inventoryItem->unit_cost,
                'reference_id'        => $data['reference_id'],
                'reference_type'      => $data['reference_type'],
                'batch_number'        => $data['batch_number'],
                'expiry_date'         => $data['expiry_date'],
                'notes'               => $data['notes'],
                'user_id'             => auth()->id(),
            ]);

            return response()->json($movement->load('item'));
        });
    }

    /** Quick stock adjustment (set absolute quantity). */
    public function adjust(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $data = $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        $diff = $data['new_quantity'] - $inventoryItem->quantity_on_hand;
        $type = $diff >= 0 ? 'adjustment' : 'adjustment';

        return DB::transaction(function () use ($inventoryItem, $data, $diff, $type) {
            $inventoryItem->update(['quantity_on_hand' => $data['new_quantity']]);

            InventoryMovement::create([
                'inventory_item_id' => $inventoryItem->id,
                'type'              => $type,
                'quantity'          => $diff,
                'unit_cost_at_time' => $inventoryItem->unit_cost,
                'reference_type'    => 'manual',
                'notes'             => $data['notes'] ?? 'Manual adjustment',
                'user_id'           => auth()->id(),
            ]);

            return response()->json($inventoryItem->fresh());
        });
    }

    /** Low stock report. */
    public function lowStock(Request $request): JsonResponse
    {
        return response()->json(
            InventoryItem::active()->lowStock()->with('vendor:id,name')->get()
        );
    }

    /** Expiring soon report. */
    public function expiring(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        return response()->json(
            InventoryItem::active()->where('track_expiry', true)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays($days)->toDateString())
                ->with('vendor:id,name')
                ->orderBy('expiry_date')
                ->get()
        );
    }

    /** Category summary. */
    public function categories(): JsonResponse
    {
        return response()->json(
            InventoryItem::active()
                ->selectRaw('category, COUNT(*) as count, SUM(quantity_on_hand * unit_cost) as total_value')
                ->groupBy('category')
                ->orderBy('category')
                ->get()
        );
    }

    /** Movement history for an item. */
    public function movements(InventoryItem $inventoryItem, Request $request): JsonResponse
    {
        $q = $inventoryItem->movements()->with('user:id,name');

        if ($from = $request->query('from')) $q->whereDate('created_at', '>=', $from);
        if ($to = $request->query('to'))   $q->whereDate('created_at', '<=', $to);
        if ($type = $request->query('type')) $q->where('type', $type);

        return response()->json($q->latest()->paginate(50));
    }
}