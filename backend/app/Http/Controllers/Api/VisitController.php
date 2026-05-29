<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AqsatContract;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VisitController extends Controller
{
    /** Today's unified queue: pending + active. Completed visits leave the queue. */
    public function queue(): JsonResponse
    {
        $visits = Visit::with('patient:id,name,phone,appointment_date')
            ->whereIn('queue_status', ['pending', 'active'])
            ->whereDate('created_at', today())
            ->orderByRaw("FIELD(queue_status, 'active', 'pending')")
            ->orderBy('created_at')
            ->get();

        return response()->json($visits);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'aqsat_contract_id' => 'nullable|exists:aqsat_contracts,id',
            'visit_type'        => 'required|in:walk_in,phone,whatsapp',
            'treatment_notes'   => 'nullable|string',
        ]);

        $data['queue_status'] = 'pending';

        return response()->json(Visit::create($data)->load('patient'), 201);
    }

    /** Single-click queue state transition: pending → active → completed. */
    public function updateStatus(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'queue_status' => 'required|in:pending,active,completed',
        ]);

        $visit->update($data);

        return response()->json($visit->load('patient'));
    }

    public function update(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'treatment_notes' => 'nullable|string',
            'total_cost'      => 'nullable|integer|min:0',
        ]);

        $visit->update($data);
        return response()->json($visit);
    }

    /** Remove a visit from the queue (only pending/active — completed visits stay in archive). */
    public function destroy(Visit $visit): JsonResponse
    {
        if ($visit->queue_status === 'completed') {
            abort(422, 'Completed visits cannot be removed from the queue.');
        }
        $visit->delete();
        return response()->json(['ok' => true]);
    }

    /** Native-camera / file upload — multipart, saved to local disk under storage/app/public/xrays. */
    public function uploadXray(Request $request, Visit $visit): JsonResponse
    {
        $request->validate([
            'xray' => 'required|file|mimes:jpg,jpeg,png,webp|max:20480', // 20 MB cap
        ]);

        // Delete previous file if replacing.
        if ($visit->xray_path && Storage::disk('public')->exists($visit->xray_path)) {
            Storage::disk('public')->delete($visit->xray_path);
        }

        $path = $request->file('xray')->store('xrays', 'public');
        $visit->update(['xray_path' => $path]);

        return response()->json([
            'xray_path' => $path,
            'url'       => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * The 3-Methodology Checkout Engine.
     * Server is the source of truth — the frontend's calculation is re-verified here.
     */
    public function checkout(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'method'      => 'required|in:full_cash,short_debt,aqsat',
            'total_cost'  => 'required|integer|min:0',
            'amount_paid' => 'required|integer|min:0',
            'aqsat_contract_id' => 'nullable|exists:aqsat_contracts,id',
        ]);

        return DB::transaction(function () use ($data, $visit) {
            $totalCost  = (int) $data['total_cost'];
            $amountPaid = (int) $data['amount_paid'];
            $debt       = 0;
            $contractId = $visit->aqsat_contract_id;

            switch ($data['method']) {
                case 'full_cash':
                    // Force amount_paid == total_cost. Zero debt.
                    $amountPaid = $totalCost;
                    $debt       = 0;
                    break;

                case 'short_debt':
                    if ($amountPaid > $totalCost) {
                        abort(422, 'amount_paid cannot exceed total_cost');
                    }
                    $debt = $totalCost - $amountPaid;
                    break;

                case 'aqsat':
                    if (empty($data['aqsat_contract_id'])) {
                        abort(422, 'aqsat_contract_id is required for installment checkout');
                    }
                    $contract = AqsatContract::lockForUpdate()->findOrFail($data['aqsat_contract_id']);

                    if ($amountPaid > $contract->remaining_balance) {
                        abort(422, 'Payment exceeds remaining installment balance');
                    }

                    // Decrement contract by today's cash, flat whole IQD.
                    $contract->remaining_balance = $contract->remaining_balance - $amountPaid;
                    if ($contract->remaining_balance === 0) {
                        $contract->status = 'completed';
                    }
                    $contract->save();

                    $contractId = $contract->id;
                    $debt       = 0; // Aqsat payments do not create short-term debt.
                    break;
            }

            $visit->update([
                'aqsat_contract_id' => $contractId,
                'total_cost'        => $totalCost,
                'amount_paid'       => $amountPaid,
                'short_term_debt'   => $debt,
                'queue_status'      => 'completed',
            ]);

            return response()->json($visit->fresh()->load('patient', 'aqsatContract'));
        });
    }

    /**
     * Pay down a partial (short-term) debt on a completed visit.
     * The patient comes back and pays some or all of what they owe.
     */
    public function payDebt(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'amount_paid' => 'required|integer|min:1',
        ]);

        if ($visit->short_term_debt <= 0) {
            abort(422, 'This visit has no outstanding debt.');
        }

        $payment = (int) $data['amount_paid'];

        if ($payment > $visit->short_term_debt) {
            abort(422, 'Payment exceeds outstanding debt.');
        }

        $visit->update([
            'amount_paid'     => $visit->amount_paid + $payment,
            'short_term_debt' => $visit->short_term_debt - $payment,
        ]);

        return response()->json($visit->fresh()->load('patient'));
    }

    /** Treatment Archive — completed visits with smart filters. */
    public function archive(Request $request): JsonResponse
    {
        $q = Visit::with('patient:id,name,phone')
            ->where('queue_status', 'completed');

        if ($from = $request->query('from')) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $q->whereDate('created_at', '<=', $to);
        }
        if ($request->boolean('with_debt')) {
            $q->where('short_term_debt', '>', 0);
        }

        return response()->json(
            $q->orderByDesc('created_at')->paginate((int) $request->query('per_page', 25))
        );
    }
}
