<?php

namespace Database\Seeders;

use App\Models\CashFlowForecast;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanInstallment;
use App\Models\Patient;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Realistic operating data for every module: vendors, stock, purchase
 * orders, installment plans and the cash-flow ledger. Idempotent — safe to
 * re-run on every deploy (updateOrCreate on natural keys).
 */
class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        // ---------- Vendors ----------
        $vendors = [
            ['name' => 'Straumann Group Iraq', 'contact_person' => 'Rebin Salih', 'phone' => '+964 751 234 5601', 'email' => 'orders@straumann-iraq.iq', 'address' => 'Ainkawa, Erbil', 'tax_number' => 'IQ-TX-88231', 'payment_terms_days' => 30, 'is_active' => true, 'notes' => 'Official distributor of Straumann implants.'],
            ['name' => 'Dentsply Sirona Supply', 'contact_person' => 'Hana Yousif', 'phone' => '+964 751 234 5602', 'email' => 'sales@dentsply-supply.com', 'address' => 'Empire World, Erbil', 'tax_number' => 'IQ-TX-51720', 'payment_terms_days' => 45, 'is_active' => true, 'notes' => 'Endodontic and restorative materials.'],
            ['name' => 'MedDent Kurdistan', 'contact_person' => 'Sarko Aziz', 'phone' => '+964 751 234 5603', 'email' => 'info@meddent-kurdistan.iq', 'address' => 'Sulaymaniyah Road, Kirkuk', 'tax_number' => 'IQ-TX-30455', 'payment_terms_days' => 15, 'is_active' => true, 'notes' => 'Fast local delivery, PPE and anesthetics.'],
            ['name' => '3M Oral Care Partner', 'contact_person' => 'Dara Omar', 'phone' => '+964 751 234 5604', 'email' => 'dara@3m-oralcare.iq', 'address' => 'Naz City, Erbil', 'tax_number' => 'IQ-TX-71908', 'payment_terms_days' => 30, 'is_active' => false, 'notes' => 'Contract paused pending 2027 pricing.'],
        ];
        $savedVendors = [];
        foreach ($vendors as $v) {
            $savedVendors[$v['name']] = Vendor::updateOrCreate(['name' => $v['name']], $v);
        }

        // ---------- Inventory ----------
        $items = [
            ['vendor' => 'Straumann Group Iraq', 'name' => 'Straumann BLX Implant 4.1x10', 'sku' => 'IMPL-BLX-4110', 'category' => 'implants', 'unit' => 'pcs', 'unit_cost' => 450000, 'sale_price' => 750000, 'quantity_on_hand' => 6, 'reorder_level' => 4, 'reorder_quantity' => 10, 'location' => 'Cabinet A1', 'track_expiry' => false],
            ['vendor' => 'Straumann Group Iraq', 'name' => 'Bone Graft Granules 0.5g', 'sku' => 'GRAFT-BG-05', 'category' => 'implants', 'unit' => 'vial', 'unit_cost' => 180000, 'sale_price' => 300000, 'quantity_on_hand' => 3, 'reorder_level' => 4, 'reorder_quantity' => 8, 'location' => 'Cabinet A2', 'track_expiry' => true, 'expiry_date' => Carbon::now()->addMonths(5)->toDateString()],
            ['vendor' => 'Dentsply Sirona Supply', 'name' => 'WaveOne Gold File 25mm', 'sku' => 'ENDO-WOG-25', 'category' => 'endodontics', 'unit' => 'pcs', 'unit_cost' => 22000, 'sale_price' => 40000, 'quantity_on_hand' => 24, 'reorder_level' => 10, 'reorder_quantity' => 20, 'location' => 'Drawer B1', 'track_expiry' => true, 'expiry_date' => Carbon::now()->addMonths(14)->toDateString()],
            ['vendor' => 'Dentsply Sirona Supply', 'name' => 'Composite Filler A2 Syringe', 'sku' => 'REST-CMP-A2', 'category' => 'restorative', 'unit' => 'syringe', 'unit_cost' => 35000, 'sale_price' => 70000, 'quantity_on_hand' => 9, 'reorder_level' => 8, 'reorder_quantity' => 15, 'location' => 'Drawer B2', 'track_expiry' => true, 'expiry_date' => Carbon::now()->addMonths(2)->toDateString()],
            ['vendor' => 'MedDent Kurdistan', 'name' => 'Lidocaine 2% Adrenaline 1:80000', 'sku' => 'ANES-LID-2', 'category' => 'anesthetics', 'unit' => 'box', 'unit_cost' => 18000, 'sale_price' => 35000, 'quantity_on_hand' => 14, 'reorder_level' => 6, 'reorder_quantity' => 12, 'location' => 'Fridge F1', 'track_expiry' => true, 'expiry_date' => Carbon::now()->addMonths(9)->toDateString()],
            ['vendor' => 'MedDent Kurdistan', 'name' => 'Nitrile Gloves M (100 pcs)', 'sku' => 'PPE-GLV-M', 'category' => 'ppe', 'unit' => 'box', 'unit_cost' => 9000, 'sale_price' => 15000, 'quantity_on_hand' => 2, 'reorder_level' => 5, 'reorder_quantity' => 20, 'location' => 'Store S1', 'track_expiry' => false],
            ['vendor' => 'MedDent Kurdistan', 'name' => 'Surgical Masks Level 3 (50 pcs)', 'sku' => 'PPE-MSK-L3', 'category' => 'ppe', 'unit' => 'box', 'unit_cost' => 7000, 'sale_price' => 12000, 'quantity_on_hand' => 11, 'reorder_level' => 5, 'reorder_quantity' => 15, 'location' => 'Store S1', 'track_expiry' => false],
            ['vendor' => 'Dentsply Sirona Supply', 'name' => 'Impression Trays Upper Metal', 'sku' => 'PROS-TRU-U', 'category' => 'prosthetics', 'unit' => 'pcs', 'unit_cost' => 25000, 'sale_price' => null, 'quantity_on_hand' => 7, 'reorder_level' => 3, 'reorder_quantity' => 5, 'location' => 'Cabinet C1', 'track_expiry' => false],
            ['vendor' => 'Straumann Group Iraq', 'name' => 'Periosteal Elevator Molt 9', 'sku' => 'SURG-ELV-9', 'category' => 'instruments', 'unit' => 'pcs', 'unit_cost' => 45000, 'sale_price' => null, 'quantity_on_hand' => 5, 'reorder_level' => 2, 'reorder_quantity' => 4, 'location' => 'Tray T3', 'track_expiry' => false],
            ['vendor' => 'MedDent Kurdistan', 'name' => 'Sterile Saline 0.9% 500ml', 'sku' => 'CONS-SAL-500', 'category' => 'consumables', 'unit' => 'bottle', 'unit_cost' => 3000, 'sale_price' => 5000, 'quantity_on_hand' => 0, 'reorder_level' => 10, 'reorder_quantity' => 24, 'location' => 'Store S2', 'track_expiry' => true, 'expiry_date' => Carbon::now()->addMonths(18)->toDateString()],
        ];
        $savedItems = [];
        foreach ($items as $i) {
            $item = InventoryItem::updateOrCreate(
                ['sku' => $i['sku']],
                collect($i)->except('vendor')->merge([
                    'vendor_id' => $savedVendors[$i['vendor']]->id,
                    'is_active' => true,
                ])->all(),
            );
            $savedItems[$i['sku']] = $item;

            // One opening-stock movement per item so the ledger is not empty.
            if ($item->quantity_on_hand > 0) {
                InventoryMovement::updateOrCreate(
                    ['inventory_item_id' => $item->id, 'type' => 'in', 'reference_type' => 'manual', 'created_at' => Carbon::now()->subDays(30)],
                    ['quantity' => $item->quantity_on_hand, 'unit_cost_at_time' => $item->unit_cost, 'user_id' => $admin?->id, 'notes' => 'Opening stock'],
                );
            }
        }

        // ---------- Purchase orders ----------
        $pos = [
            ['vendor' => 'Straumann Group Iraq', 'po_number' => 'PO-2026-001', 'status' => 'received', 'order_date' => Carbon::now()->subDays(25)->toDateString(), 'expected_date' => Carbon::now()->subDays(18)->toDateString(),
             'items' => [['sku' => 'IMPL-BLX-4110', 'q' => 6, 'cost' => 450000], ['sku' => 'GRAFT-BG-05', 'q' => 3, 'cost' => 180000]]],
            ['vendor' => 'Dentsply Sirona Supply', 'po_number' => 'PO-2026-002', 'status' => 'partial', 'order_date' => Carbon::now()->subDays(10)->toDateString(), 'expected_date' => Carbon::now()->addDays(4)->toDateString(),
             'items' => [['sku' => 'ENDO-WOG-25', 'q' => 20, 'cost' => 22000], ['sku' => 'REST-CMP-A2', 'q' => 10, 'cost' => 35000]]],
            ['vendor' => 'MedDent Kurdistan', 'po_number' => 'PO-2026-003', 'status' => 'sent', 'order_date' => Carbon::now()->subDays(2)->toDateString(), 'expected_date' => Carbon::now()->addDays(6)->toDateString(),
             'items' => [['sku' => 'PPE-GLV-M', 'q' => 20, 'cost' => 9000], ['sku' => 'CONS-SAL-500', 'q' => 24, 'cost' => 3000]]],
        ];
        foreach ($pos as $poData) {
            $subtotal = 0;
            $lines = [];
            foreach ($poData['items'] as $line) {
                $lineTotal = $line['q'] * $line['cost'];
                $subtotal += $lineTotal;
                $lines[] = $line + ['line_total' => $lineTotal];
            }
            $po = PurchaseOrder::updateOrCreate(
                ['po_number' => $poData['po_number']],
                [
                    'vendor_id' => $savedVendors[$poData['vendor']]->id,
                    'order_date' => $poData['order_date'],
                    'expected_date' => $poData['expected_date'],
                    'status' => $poData['status'],
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
                    'total_amount' => $subtotal,
                ],
            );
            foreach ($lines as $line) {
                PurchaseOrderItem::updateOrCreate(
                    ['purchase_order_id' => $po->id, 'inventory_item_id' => $savedItems[$line['sku']]->id],
                    ['quantity_ordered' => $line['q'], 'quantity_received' => $poData['status'] === 'received' ? $line['q'] : 0, 'unit_cost' => $line['cost'], 'line_total' => $line['line_total']],
                );
            }
        }

        // ---------- Payment plans ----------
        $planSpecs = [
            ['phone' => '+964 750 123 4501', 'name' => 'Crown & Bridge Plan', 'total' => 1500000, 'down' => 300000, 'installment' => 200000, 'count' => 6, 'freq' => 30, 'start' => Carbon::now()->subMonths(2), 'status' => 'active',
             'paid' => 3, 'partial' => 0, 'overdue' => 1],
            ['phone' => '+964 750 123 4503', 'name' => 'Orthodontic Braces Plan', 'total' => 3600000, 'down' => 600000, 'installment' => 250000, 'count' => 12, 'freq' => 30, 'start' => Carbon::now()->subMonths(4), 'status' => 'active',
             'paid' => 4, 'partial' => 1, 'overdue' => 0],
            ['phone' => '+964 750 123 4505', 'name' => 'Full Mouth Scaling Plan', 'total' => 600000, 'down' => 0, 'installment' => 100000, 'count' => 6, 'freq' => 30, 'start' => Carbon::now()->subMonths(3), 'status' => 'defaulted',
             'paid' => 1, 'partial' => 0, 'overdue' => 2],
            ['phone' => '+964 750 123 4507', 'name' => 'Denture Payment Plan', 'total' => 950000, 'down' => 250000, 'installment' => 175000, 'count' => 4, 'freq' => 30, 'start' => Carbon::now()->subMonths(5), 'status' => 'completed',
             'paid' => 4, 'partial' => 0, 'overdue' => 0],
            ['phone' => '+964 750 123 4502', 'name' => 'Implant Placement Plan', 'total' => 3000000, 'down' => 500000, 'installment' => 500000, 'count' => 5, 'freq' => 30, 'start' => Carbon::now()->addDays(7), 'status' => 'active',
             'paid' => 0, 'partial' => 0, 'overdue' => 0],
        ];
        foreach ($planSpecs as $spec) {
            $patient = Patient::where('phone', $spec['phone'])->first();
            if (! $patient) continue;

            $plan = PaymentPlan::updateOrCreate(
                ['patient_id' => $patient->id, 'name' => $spec['name']],
                [
                    'total_amount' => $spec['total'],
                    'down_payment' => $spec['down'],
                    'installment_amount' => $spec['installment'],
                    'frequency_days' => $spec['freq'],
                    'installment_count' => $spec['count'],
                    'start_date' => $spec['start']->toDateString(),
                    'status' => $spec['status'],
                    'notes' => null,
                ],
            );

            // Rebuild installments deterministically.
            $plan->installments()->delete();
            for ($n = 1; $n <= $spec['count']; $n++) {
                $due = $spec['start']->copy()->addDays(($n - 1) * $spec['freq']);

                if ($n <= $spec['paid']) {
                    $status = 'paid'; $amountPaid = $spec['installment']; $paidDate = $due->copy()->addDays(1);
                } elseif ($spec['partial'] > 0 && $n === $spec['paid'] + 1) {
                    $status = 'partial'; $amountPaid = intdiv($spec['installment'], 2); $paidDate = null;
                } elseif ($spec['overdue'] > 0 && $n <= $spec['paid'] + $spec['partial'] + $spec['overdue'] && $due->isPast()) {
                    $status = 'overdue'; $amountPaid = 0; $paidDate = null;
                } elseif ($due->isPast()) {
                    $status = 'overdue'; $amountPaid = 0; $paidDate = null;
                } else {
                    $status = 'pending'; $amountPaid = 0; $paidDate = null;
                }

                PaymentPlanInstallment::create([
                    'payment_plan_id' => $plan->id,
                    'installment_number' => $n,
                    'due_date' => $due->toDateString(),
                    'amount' => $spec['installment'],
                    'amount_paid' => min($amountPaid, $spec['installment']),
                    'status' => $status,
                    'paid_date' => $paidDate?->toDateString(),
                ]);
            }
        }

        // ---------- Cash flow manual entries ----------
        $entries = [
            ['date' => Carbon::now()->addDays(2),  'type' => 'outflow', 'source' => 'manual', 'description' => 'Dental lab payment — crown batch', 'amount' => 420000, 'status' => 'confirmed'],
            ['date' => Carbon::now()->addDays(5),  'type' => 'outflow', 'source' => 'manual', 'description' => 'Clinic rent — September', 'amount' => 1200000, 'status' => 'projected'],
            ['date' => Carbon::now()->addDays(9),  'type' => 'outflow', 'source' => 'manual', 'description' => 'Generator fuel & maintenance', 'amount' => 90000, 'status' => 'projected'],
            ['date' => Carbon::now()->addDays(12), 'type' => 'inflow',  'source' => 'manual', 'description' => 'Expected walk-in revenue (weekly)', 'amount' => 900000, 'status' => 'projected'],
            ['date' => Carbon::now()->addDays(20), 'type' => 'outflow', 'source' => 'manual', 'description' => 'Staff salaries', 'amount' => 3500000, 'status' => 'projected'],
            ['date' => Carbon::now()->addDays(27), 'type' => 'inflow',  'source' => 'manual', 'description' => 'Insurance reimbursement — Q3 batch', 'amount' => 1500000, 'status' => 'projected'],
        ];
        foreach ($entries as $e) {
            CashFlowForecast::updateOrCreate(
                ['forecast_date' => $e['date']->toDateString(), 'description' => $e['description']],
                ['type' => $e['type'], 'source' => $e['source'], 'amount' => $e['amount'], 'status' => $e['status']],
            );
        }
    }
}
