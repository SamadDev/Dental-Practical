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

    /**
     * Treatment Archive — completed visits with smart filters.
     *
     * Installment-contract payments are FOLDED into one synthetic row per
     * contract so the user sees "the contract" instead of one row per
     * installment. Walk-in / short-debt visits stay one-row-each.
     */
    public function archive(Request $request): JsonResponse
    {
        $from      = $request->query('from');
        $to        = $request->query('to');
        $withDebt  = $request->boolean('with_debt');
        $aqsatOnly = $request->boolean('aqsat_only');
        // Free-text search across patient name/phone, treatment notes, and
        // contract treatment name. Trimmed; empty string is treated as absent.
        $search    = trim((string) $request->query('search', ''));
        $like      = $search === '' ? null : '%' . $search . '%';

        // --- Non-contract completed visits (walk-in / short-debt) ----------
        //
        // Skipped entirely when the user narrowed to installment visits only.
        $nonContract = collect();
        if (!$aqsatOnly) {
            $q = Visit::with('patient:id,name,phone')
                ->where('queue_status', 'completed')
                ->whereNull('aqsat_contract_id');

            if ($from)     $q->whereDate('created_at', '>=', $from);
            if ($to)       $q->whereDate('created_at', '<=', $to);
            if ($withDebt) $q->where('short_term_debt', '>', 0);

            // Match against the visit's own notes OR the related patient's
            // name/phone. Wrapped so it doesn't break the AND-chain above.
            if ($like) {
                $q->where(function ($w) use ($like) {
                    $w->where('treatment_notes', 'like', $like)
                      ->orWhereHas('patient', function ($p) use ($like) {
                          $p->where('name',  'like', $like)
                            ->orWhere('phone', 'like', $like);
                      });
                });
            }

            $nonContract = $q->get()->map(fn ($v) => [
                'id'                => $v->id,
                'patient'           => $v->patient,
                'created_at'        => $v->created_at,
                'total_cost'        => $v->total_cost,
                'amount_paid'       => $v->amount_paid,
                'short_term_debt'   => $v->short_term_debt,
                'treatment_notes'   => $v->treatment_notes,
                'aqsat_contract'    => null,
                'aqsat_contract_id' => null,
                'is_contract_row'   => false,
            ]);
        }

        // --- Contract rows: one row per AqsatContract that has at least one
        //     completed payment-visit in the date window. Short-term-debt
        //     filter excludes contracts entirely (installments don't carry
        //     short-term debt). ----------------------------------------------
        $contracts = collect();
        if (!$withDebt) {
            $cq = AqsatContract::with(['patient:id,name,phone'])
                ->whereHas('visits', function ($v) use ($from, $to) {
                    $v->where('queue_status', 'completed')
                      ->where('amount_paid', '>', 0);
                    if ($from) $v->whereDate('created_at', '>=', $from);
                    if ($to)   $v->whereDate('created_at', '<=', $to);
                })
                ->with(['visits' => function ($v) use ($from, $to) {
                    $v->where('queue_status', 'completed')
                      ->where('amount_paid', '>', 0);
                    if ($from) $v->whereDate('created_at', '>=', $from);
                    if ($to)   $v->whereDate('created_at', '<=', $to);
                }]);

            // Contract rows match if the search term hits the treatment name
            // OR the linked patient's name/phone. (Visit treatment_notes is
            // irrelevant here — installment visits don't carry notes.)
            if ($like) {
                $cq->where(function ($w) use ($like) {
                    $w->where('treatment_name', 'like', $like)
                      ->orWhereHas('patient', function ($p) use ($like) {
                          $p->where('name',  'like', $like)
                            ->orWhere('phone', 'like', $like);
                      });
                });
            }

            $contracts = $cq->get()->map(function (AqsatContract $c) {
                // Anchor the row at the most recent payment so it sorts where
                // the user expects ("when did anything last happen on this
                // contract?").
                $latest = $c->visits->max('created_at');

                return [
                    'id'                => 'contract-' . $c->id,
                    'patient'           => $c->patient,
                    'created_at'        => $latest,
                    'total_cost'        => $c->total_amount,
                    // Sum of every payment so far on this contract — the
                    // "Total Amount Paid" the user wanted to see.
                    'amount_paid'       => (int) $c->paid_amount,
                    'short_term_debt'   => 0,
                    'treatment_notes'   => null,
                    'aqsat_contract'    => [
                        'id'             => $c->id,
                        'treatment_name' => $c->treatment_name,
                        'status'         => $c->status,
                    ],
                    'aqsat_contract_id' => $c->id,
                    'is_contract_row'   => true,
                ];
            });
        }

        $merged = $nonContract->concat($contracts)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($merged);
    }
}
