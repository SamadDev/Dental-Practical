<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AqsatContract;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AqsatContractController extends Controller
{
    /** Accessors appended on every response so the frontend can render progress. */
    private const APPENDED = [
        'paid_amount', 'paid_installments',
        'total_installments', 'remaining_installments',
        'next_due_date', 'expected_completion_date',
    ];

    public function index(Request $request): JsonResponse
    {
        $q = AqsatContract::with('patient:id,name,phone');

        if ($pid = $request->query('patient_id')) {
            $q->where('patient_id', $pid);
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $rows = $q->orderByDesc('id')->get();
        $rows->each->append(self::APPENDED);

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'treatment_name'     => 'required|string|max:255',
            'total_amount'       => 'required|integer|min:1',
            // Per-installment cash. Must be >= 1 and <= total_amount.
            'installment_amount' => 'required|integer|min:1|lte:total_amount',
        ]);

        // remaining_balance starts equal to total_amount — both flat whole IQD.
        $data['remaining_balance'] = $data['total_amount'];
        $data['status']            = 'active';

        $contract = AqsatContract::create($data);
        $contract->append(self::APPENDED);

        return response()->json($contract, 201);
    }

    public function show(AqsatContract $aqsatContract): JsonResponse
    {
        $this->loadForDetail($aqsatContract);
        return response()->json($this->withPaymentHistory($aqsatContract));
    }

    /** Shared loader: patient + payment-bearing visits, newest first. */
    private function loadForDetail(AqsatContract $contract): void
    {
        $contract->load([
            'patient:id,name,phone',
            'visits' => fn ($q) => $q
                ->where('amount_paid', '>', 0)
                ->orderByDesc('created_at'),
        ]);
        $contract->append(self::APPENDED);
    }

    /**
     * Wrap the contract for the detail-dialog response with a `visits` payload
     * that includes a running `remaining_after` per payment. We walk the
     * visits oldest→newest with a running paid total so each entry knows what
     * was left right after that payment cleared.
     */
    private function withPaymentHistory(AqsatContract $contract): array
    {
        $payload = $contract->toArray();

        $total    = (int) $contract->total_amount;
        $paidSoFar = 0;

        // Oldest→newest so the running balance ticks correctly. Reverse for
        // display because the dialog renders newest-first.
        $history = $contract->visits
            ->sortBy('created_at')
            ->values()
            ->map(function ($v) use (&$paidSoFar, $total) {
                $paidSoFar += (int) $v->amount_paid;
                return [
                    'id'              => $v->id,
                    'created_at'      => $v->created_at,
                    'amount_paid'     => (int) $v->amount_paid,
                    'remaining_after' => max(0, $total - $paidSoFar),
                ];
            })
            ->reverse()
            ->values();

        $payload['visits'] = $history;
        return $payload;
    }

    /**
     * Record an installment payment against an active contract.
     * Creates a payment-only Visit row so the cash shows in the dashboard /
     * archive AND in the contract's payment history, then decrements the
     * contract balance atomically.
     */
    public function payInstallment(Request $request, AqsatContract $aqsatContract): JsonResponse
    {
        $data = $request->validate([
            'amount_paid' => 'required|integer|min:1',
        ]);

        if ($aqsatContract->status !== 'active') {
            abort(422, 'Contract is not active.');
        }
        if ($aqsatContract->remaining_balance <= 0) {
            abort(422, 'Contract has no remaining balance.');
        }

        $payment = (int) $data['amount_paid'];
        if ($payment > $aqsatContract->remaining_balance) {
            abort(422, 'Payment exceeds remaining contract balance.');
        }

        return DB::transaction(function () use ($aqsatContract, $payment) {
            $contract = AqsatContract::lockForUpdate()->findOrFail($aqsatContract->id);

            // Payment-only visit — no treatment notes, no debt. visit_type
            // 'phone' is used as a neutral "follow-up payment" channel.
            Visit::create([
                'patient_id'        => $contract->patient_id,
                'aqsat_contract_id' => $contract->id,
                'visit_type'        => 'phone',
                'queue_status'      => 'completed',
                'total_cost'        => $payment,
                'amount_paid'       => $payment,
                'short_term_debt'   => 0,
            ]);

            $contract->remaining_balance -= $payment;
            if ($contract->remaining_balance === 0) {
                $contract->status = 'completed';
            }
            $contract->save();

            $this->loadForDetail($contract);
            return response()->json($this->withPaymentHistory($contract));
        });
    }

    public function update(Request $request, AqsatContract $aqsatContract): JsonResponse
    {
        $data = $request->validate([
            'treatment_name' => 'sometimes|string|max:255',
            'status'         => 'sometimes|in:active,completed,cancelled',
        ]);

        $aqsatContract->update($data);
        $aqsatContract->append(self::APPENDED);

        return response()->json($aqsatContract);
    }
}
