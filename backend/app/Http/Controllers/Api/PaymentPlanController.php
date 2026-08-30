<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanInstallment;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentPlanController extends Controller
{
    /** sort key => column, whitelisted so the query string can't drive a raw ORDER BY. */
    private const SORTABLE = [
        'name'         => 'name',
        'total_amount' => 'total_amount',
        'start_date'   => 'start_date',
        'status'       => 'status',
        'created_at'   => 'created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $q = PaymentPlan::with(['patient:id,name,phone', 'installments']);

        if ($pid = $request->query('patient_id')) {
            $q->where('patient_id', $pid);
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($request->boolean('with_overdue')) {
            $q->whereHas('installments', fn ($i) => $i->whereIn('status', ['overdue', 'partial']));
        }
        if ($s = trim((string) $request->query('search'))) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhereHas('patient', fn ($p) => $p
                        ->where('name', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%"));
            });
        }

        $sort = self::SORTABLE[(string) $request->query('sort')] ?? 'created_at';
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $q->orderBy($sort, $dir)->orderByDesc('id');

        $perPage = max(5, min(200, (int) $request->query('per_page', 25) ?: 25));

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'visit_id'           => 'nullable|exists:visits,id',
            'name'               => 'required|string|max:255',
            'total_amount'       => 'required|integer|min:1',
            'down_payment'       => 'sometimes|integer|min:0',
            'installment_amount' => 'required|integer|min:1',
            'frequency_days'     => 'sometimes|integer|min:1|max:365',
            'installment_count'  => 'required|integer|min:1|max:120',
            'start_date'         => 'required|date',
            'notes'              => 'nullable|string',
        ]);

        $downPayment = (int) ($data['down_payment'] ?? 0);
        if ($downPayment + ($data['installment_amount'] * $data['installment_count']) > $data['total_amount']) {
            return response()->json(['message' => 'Installments + down payment exceed total amount'], 422);
        }

        return DB::transaction(function () use ($data, $downPayment) {
            $plan = PaymentPlan::create($data + ['down_payment' => $downPayment]);

            // Create installments
            $start = \Carbon\Carbon::parse($data['start_date']);
            for ($i = 1; $i <= $data['installment_count']; $i++) {
                PaymentPlanInstallment::create([
                    'payment_plan_id'   => $plan->id,
                    'installment_number'=> $i,
                    'due_date'          => $start->copy()->addDays(($i - 1) * $data['frequency_days'])->toDateString(),
                    'amount'            => $data['installment_amount'],
                    'status'            => 'pending',
                ]);
            }

            // Record down payment if any
            if ($downPayment > 0) {
                $first = $plan->installments()->first();
                if ($first) {
                    $first->update([
                        'amount_paid' => min($downPayment, $first->amount),
                        'status'      => $downPayment >= $first->amount ? 'paid' : 'partial',
                        'paid_date'   => $downPayment >= $first->amount ? now()->toDateString() : null,
                    ]);
                }
            }

            return response()->json($plan->load('installments'), 201);
        });
    }

    public function show(PaymentPlan $paymentPlan): JsonResponse
    {
        return response()->json($paymentPlan->load(['patient', 'visit', 'installments']));
    }

    public function update(Request $request, PaymentPlan $paymentPlan): JsonResponse
    {
        $data = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'status'             => 'sometimes|in:active,completed,defaulted,cancelled',
            'notes'              => 'nullable|string',
        ]);

        $paymentPlan->update($data);
        return response()->json($paymentPlan->load('installments'));
    }

    public function destroy(PaymentPlan $paymentPlan): JsonResponse
    {
        $paymentPlan->delete();
        return response()->json(['ok' => true]);
    }

    /** Record a payment against an installment. */
    public function payInstallment(Request $request, PaymentPlanInstallment $installment): JsonResponse
    {
        $data = $request->validate([
            'amount'     => 'required|integer|min:1',
            'paid_date'  => 'sometimes|date',
            'notes'      => 'nullable|string',
        ]);

        if ($installment->status === 'paid' || $installment->status === 'waived') {
            return response()->json(['message' => 'Installment already settled'], 422);
        }

        $newPaid = $installment->amount_paid + $data['amount'];
        if ($newPaid > $installment->amount) {
            return response()->json(['message' => 'Payment exceeds installment amount'], 422);
        }

        $status = $newPaid >= $installment->amount ? 'paid' : 'partial';
        $installment->update([
            'amount_paid' => $newPaid,
            'status'      => $status,
            'paid_date'   => $status === 'paid' ? ($data['paid_date'] ?? now()->toDateString()) : $installment->paid_date,
            'notes'       => $data['notes'] ?? $installment->notes,
        ]);

        // Check if plan is complete
        $plan = $installment->plan;
        if ($plan->installments()->whereIn('status', ['pending', 'partial', 'overdue'])->count() === 0) {
            $plan->update(['status' => 'completed']);
        }

        return response()->json($installment->fresh());
    }

    /** Mark installment as waived. */
    public function waiveInstallment(PaymentPlanInstallment $installment): JsonResponse
    {
        $installment->update(['status' => 'waived', 'amount_paid' => $installment->amount]);
        $plan = $installment->plan;
        if ($plan->installments()->whereIn('status', ['pending', 'partial', 'overdue'])->count() === 0) {
            $plan->update(['status' => 'completed']);
        }
        return response()->json($installment->fresh());
    }

    /** Overdue report. */
    public function overdue(Request $request): JsonResponse
    {
        $installments = PaymentPlanInstallment::with(['plan.patient:id,name,phone'])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get()
            ->map(function ($i) {
                $daysOverdue = now()->diffInDays($i->due_date);
                return [
                    'installment' => $i,
                    'days_overdue' => $daysOverdue,
                    'patient' => $i->plan->patient,
                ];
            });

        return response()->json($installments);
    }

    /** Upcoming payments (next 30 days). */
    public function upcoming(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);
        $installments = PaymentPlanInstallment::with(['plan.patient:id,name,phone'])
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('due_date')
            ->get();

        return response()->json($installments);
    }
}