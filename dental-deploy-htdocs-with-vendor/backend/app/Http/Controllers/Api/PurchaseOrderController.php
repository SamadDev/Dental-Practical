<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'items.item']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sort = $request->get('sort', 'created_at');
        $dir  = $request->get('dir', 'desc');
        $query->orderBy($sort, $dir);

        $perPage = $request->get('per_page', 25);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id'     => 'required|exists:vendors,id',
            'po_number'     => 'required|string|max:50|unique:purchase_orders,po_number',
            'order_date'    => 'required|date',
            'expected_date' => 'nullable|date',
            'items'         => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_ordered'  => 'required|integer|min:1',
            'items.*.unit_cost'         => 'required|integer|min:0',
        ]);

        $total = collect($data['items'])->sum(function ($i) {
            return $i['quantity_ordered'] * $i['unit_cost'];
        });

        $po = PurchaseOrder::create([
            'vendor_id'     => $data['vendor_id'],
            'po_number'     => $data['po_number'],
            'order_date'    => $data['order_date'],
            'expected_date' => $data['expected_date'] ?? null,
            'total_amount'  => $total,
            'status'        => 'draft',
        ]);

        foreach ($data['items'] as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id'   => $po->id,
                'inventory_item_id'   => $item['inventory_item_id'],
                'quantity_ordered'    => $item['quantity_ordered'],
                'quantity_received'   => 0,
                'unit_cost'           => $item['unit_cost'],
            ]);
        }

        return response()->json($po->load('items.item', 'vendor'), 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->load(['vendor', 'items.item']);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received'      => 'required|integer|min:1',
            'items.*.batch_number'           => 'nullable|string|max:100',
            'items.*.expiry_date'            => 'nullable|date',
        ]);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $allReceived = true;

            foreach ($data['items'] as $itemData) {
                $poItem = PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);

                if ($poItem->purchase_order_id !== $purchaseOrder->id) {
                    abort(422, 'Item does not belong to this purchase order');
                }

                $newReceived = $poItem->quantity_received + $itemData['quantity_received'];
                $poItem->update([
                    'quantity_received' => $newReceived,
                ]);

                // Update inventory
                $inventoryItem = $poItem->item;
                $inventoryItem->increment('quantity_on_hand', $itemData['quantity_received']);

                // Record movement
                \App\Models\InventoryMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type'              => 'in',
                    'quantity'          => $itemData['quantity_received'],
                    'batch_number'      => $itemData['batch_number'] ?? null,
                ]);

                if ($newReceived < $poItem->quantity_ordered) {
                    $allReceived = false;
                }
            }

            $purchaseOrder->update([
                'status' => $allReceived ? 'received' : 'partial',
            ]);
        });

        return $purchaseOrder->fresh()->load('items.item');
    }
}