<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\HandlesDataTableQueries;
use App\Http\Controllers\Controller;
use App\Models\AqsatContract;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VisitController extends Controller
{
    use HandlesDataTableQueries;

    /** Archive sort keys => columns. `patient` sorts via a correlated subquery. */
    private const ARCHIVE_SORTABLE = [
        'created_at'      => 'visits.created_at',
        'total_cost'      => 'visits.total_cost',
        'amount_paid'     => 'visits.amount_paid',
        'short_term_debt' => 'visits.short_term_debt',
        'visit_type'      => 'visits.visit_type',
        'patient'         => 'patient_name',
    ];

    /** Today's unified queue: pending + active. Completed visits leave the queue. */
    public function queue(Request $request): JsonResponse
    {
        $user = $request->user();
        $visits = Visit::with('patient:id,name,phone,appointment_date,doctor_id')
            ->with('doctor:id,name,color,specialty')
            ->whereIn('queue_status', ['pending', 'active'])
            ->whereDate('created_at', today())
            // CASE instead of FIELD() — works on both MySQL (prod) and SQLite (dev).
            ->orderByRaw("CASE queue_status WHEN 'active' THEN 0 ELSE 1 END")
            ->orderBy('created_at');

        $this->applyVisitScope($visits, $request);

        return response()->json($visits->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'aqsat_contract_id' => 'nullable|exists:aqsat_contracts,id',
            'visit_type'        => 'required|in:walk_in,phone,whatsapp',
            'treatment_name'    => 'nullable|string|max:255',
            'treatment_notes'   => 'nullable|string',
            'doctor_id'         => 'nullable|exists:doctors,id',
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
            'treatment_name'  => 'nullable|string|max:255',
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
        $q = $this->archiveQuery($request)
            ->select('visits.*')
            ->with('patient:id,name,phone')
            // Selected as an alias so the table can sort by patient name
            // without a join that would complicate the totals query.
            ->addSelect([
                'patient_name' => Patient::select('name')
                    ->whereColumn('patients.id', 'visits.patient_id'),
            ]);

        $this->applySort($q, $request, self::ARCHIVE_SORTABLE, 'created_at');

        $page = $q->paginate($this->perPage($request));

        // Grand totals over every matching row, not just the current page —
        // the printed ledger's bottom line has to cover the whole filter set.
        $totals = $this->archiveQuery($request)
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total')
            ->selectRaw('COALESCE(SUM(amount_paid), 0) as paid')
            ->selectRaw('COALESCE(SUM(short_term_debt), 0) as debt')
            ->first();

        return response()->json([
            ...$page->toArray(),
            'totals' => [
                'total' => (int) $totals->total,
                'paid'  => (int) $totals->paid,
                'debt'  => (int) $totals->debt,
            ],
        ]);
    }

    /**
     * The archive's filtered base query. Built twice per request — once for the
     * page of rows, once for the totals — so it must stay side-effect free.
     */
    private function archiveQuery(Request $request): Builder
    {
        $q = Visit::query()->where('visits.queue_status', 'completed');

        if ($s = trim((string) $request->query('search'))) {
            $q->where(function ($w) use ($s) {
                $w->where('visits.treatment_notes', 'like', "%{$s}%")
                    ->orWhereHas('patient', fn ($p) => $p
                        ->where('name', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%"));
            });
        }

        $this->applyDateRange($q, $request, 'visits.created_at');
        $this->applyAmountRange($q, $request, 'visits.total_cost', 'min_total', 'max_total');

        if ($request->boolean('with_debt')) {
            $q->where('visits.short_term_debt', '>', 0);
        }

        // Settlement state is derived, not stored — express it as a predicate
        // on short_term_debt rather than adding a column that could drift.
        match ((string) $request->query('settlement')) {
            'settled'     => $q->where('visits.short_term_debt', '=', 0),
            'outstanding' => $q->where('visits.short_term_debt', '>', 0),
            default       => null,
        };

        if ($type = $request->query('visit_type')) {
            $q->where('visits.visit_type', $type);
        }

        if ($request->boolean('has_xray')) {
            $q->whereNotNull('visits.xray_path');
        }

        if ($request->filled('patient_id')) {
            $q->where('visits.patient_id', (int) $request->query('patient_id'));
        }

        return $q;
    }


    /**
     * Scope a visit query by the user's accessible doctors.
     * - Admin sees all unless ?doctor_id is set.
     * - Doctor / hygienist sees own.
     * - Receptionist sees assigned.
     * - ?doctor_id=N narrows the scope to that one doctor (for any role).
     */
    private function applyVisitScope(Builder $q, Request $request): void
    {
        $user = $request->user();
        $ids  = $user->accessibleDoctorIds();

        if ($request->filled('doctor_id')) {
            $q->where('visits.doctor_id', (int) $request->query('doctor_id'));
            return;
        }

        if (empty($ids)) {
            if (! $user->isAdmin()) {
                $q->whereRaw('0 = 1');
            }
            return;
        }

        if (! $user->isAdmin()) {
            $q->whereIn('visits.doctor_id', $ids);
        }
    }
}