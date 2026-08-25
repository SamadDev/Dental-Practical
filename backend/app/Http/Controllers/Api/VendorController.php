<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Vendor::query()
            ->when($request->boolean('active_only', true), fn ($qq) => $qq->where('is_active', true))
            ->when($s = $request->query('search'), fn ($qq) => $qq->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")->orWhere('contact_person', 'like', "%{$s}%");
            }))
            ->orderBy('name');

        return response()->json($q->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'contact_person'       => 'nullable|string|max:255',
            'phone'                => 'nullable|string|max:50',
            'email'                => 'nullable|email|max:255',
            'address'              => 'nullable|string',
            'tax_number'           => 'nullable|string|max:100',
            'payment_terms_days'   => 'sometimes|integer|min:0',
            'is_active'            => 'sometimes|boolean',
            'notes'                => 'nullable|string',
        ]);

        return response()->json(Vendor::create($data), 201);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return response()->json($vendor->load(['items', 'purchaseOrders' => fn ($q) => $q->latest()->limit(10)]));
    }

    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $data = $request->validate([
            'name'                 => 'sometimes|string|max:255',
            'contact_person'       => 'nullable|string|max:255',
            'phone'                => 'nullable|string|max:50',
            'email'                => 'nullable|email|max:255',
            'address'              => 'nullable|string',
            'tax_number'           => 'nullable|string|max:100',
            'payment_terms_days'   => 'sometimes|integer|min:0',
            'is_active'            => 'sometimes|boolean',
            'notes'                => 'nullable|string',
        ]);

        $vendor->update($data);
        return response()->json($vendor);
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        $vendor->delete();
        return response()->json(['ok' => true]);
    }

    /** Vendor's items. */
    public function items(Vendor $vendor, Request $request): JsonResponse
    {
        $q = $vendor->items()->when($request->boolean('low_stock'), fn ($qq) => $qq->lowStock());
        return response()->json($q->orderBy('name')->paginate(50));
    }

    // ========== Purchase Orders ==========

    public function purchaseOrders(Request $request): JsonResponse
    {
        $q = PurchaseOrder::with('vendor:id,name')
            ->when($request->query('vendor_id'), fn ($qq, $v) => $qq->where('vendor_id', $v))
            ->when($request->query('status'), fn ($qq, $s) => $qq->where('status', $s))
            ->when($from = $request->query('from'), fn ($qq) => $qq->whereDate('order_date', '>=', $from))
            ->when($to = $request->query('to'), fn ($qq) => $qq->whereDate('order_date', '<=', $to))
            ->orderByDesc('order_date');

        return response()->json($q->paginate(25));
    }

    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id'      => 'required|exists:vendors,id',
            'po_number'      => 'required|string|max:50|unique:purchase_orders',
            'order_date'     => 'required|date',
            'expected_date'  => 'nullable|date',
            'items'          => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_ordered'  => 'required|integer|min:1',
            'items.*.unit_cost'         => 'required|integer|min:0',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity_ordered'] * $item['unit_cost'];
            }

            $po = PurchaseOrder::create([
                'vendor_id'    => $data['vendor_id'],
                'po_number'    => $data['po_number'],
                'order_date'   => $data['order_date'],
                'expected_date'=> $data['expected_date'],
                'subtotal'     => $subtotal,
                'tax_amount'   => 0,
                'total_amount' => $subtotal,
                'status'       => 'draft',
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id'  => $po->id,
                    'inventory_item_id'  => $item['inventory_item_id'],
                    'quantity_ordered'   => $item['quantity_ordered'],
                    'unit_cost'          => $item['unit_cost'],
                    'line_total'         => $item['quantity_ordered'] * $item['unit_cost'],
                ]);
            }

            return response()->json($po->load(['vendor', 'items.item']), 201);
        });
    }

    public function showPurchaseOrder(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json($purchaseOrder->load(['vendor', 'items.item']));
    }

    public function updatePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $data = $request->validate([
            'expected_date' => 'nullable|date',
            'status'        => 'sometimes|in:draft,sent,confirmed,partial,received,cancelled',
            'notes'         => 'nullable|string',
        ]);

        $purchaseOrder->update($data);
        return response()->json($purchaseOrder->load(['vendor', 'items.item']));
    }

    /** Receive items against a PO. */
    public function receivePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received'      => 'required|integer|min:1',
            'items.*.batch_number'           => 'nullable|string|max:100',
            'items.*.expiry_date'            => 'nullable|date',
        ]);

        return DB::transaction(function () use ($purchaseOrder, $data) {
            $allReceived = true;

            foreach ($data['items'] as $item) {
                $poItem = PurchaseOrderItem::findOrFail($item['purchase_order_item_id']);
                $newReceived = $poItem->quantity_received + $item['quantity_received'];

                if ($newReceived > $poItem->quantity_ordered) {
                    abort(422, "Received quantity exceeds ordered for item {$poItem->id}");
                }

                $poItem->update(['quantity_received' => $newReceived]);

                // Create inventory movement
                $inventoryItem = $poItem->item;
                $inventoryItem->increment('quantity_on_hand', $item['quantity_received']);

                InventoryMovement::create([
                    'inventory_item_id'   => $inventoryItem->id,
                    'type'                => 'in',
                    'quantity'            => $item['quantity_received'],
                    'unit_cost_at_time'   => $poItem->unit_cost,
                    'reference_type'      => 'purchase_order',
                    'reference_id'        => $purchaseOrder->id,
                    'batch_number'        => $item['batch_number'],
                    'expiry_date'         => $item['expiry_date'],
                    'notes'               => "PO: {$purchaseOrder->po_number}",
                    'user_id'             => auth()->id(),
                ]);

                if ($newReceived < $poItem->quantity_ordered) {
                    $allReceived = false;
                }
            }

            if ($allReceived) {
                $purchaseOrder->update(['status' => 'received']);
            } elseif ($purchaseOrder->status === 'draft' || $purchaseOrder->status === 'sent') {
                $purchaseOrder->update(['status' => 'partial']);
            }

            return response()->json($purchaseOrder->fresh()->load(['vendor', 'items.item']));
        });
    }
}