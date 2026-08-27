<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentPlan::with(['patient', 'installments']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            })->orWhere('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->whereHas('installments', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $sort = $request->get('sort', 'created_at');
        $dir  = $request->get('dir', 'desc');
        $query->orderBy($sort, $dir);

        $perPage = $request->get('per_page', 25);
        $plans = $query->paginate($perPage);

        // Calculate remaining and settled for each plan
        $plans->getCollection()->transform(function ($plan) {
            $installments = $plan->installments;
            $totalPaid = $plan->down_payment + $installments->sum('amount_paid');
            $plan->remaining = max(0, $plan->total_amount - $totalPaid);
            $plan->settled_count = $installments->whereIn('status', ['paid', 'waived'])->count();
            return $plan;
        });

        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'          => 'required|exists:patients,id',
            'name'                => 'required|string|max:255',
            'total_amount'        => 'required|integer|min:1',
            'down_payment'        => 'integer|min:0',
            'installment_amount'  => 'required|integer|min:1',
            'installment_count'   => 'required|integer|min:1|max:120',
            'frequency_days'      => 'integer|in:7,30',
            'start_date'          => 'required|date',
        ]);

        $plan = PaymentPlan::create($data);

        // Create installments
        $dueDate = $data['start_date'];
        for ($i = 1; $i <= $data['installment_count']; $i++) {
            PaymentPlanInstallment::create([
                'payment_plan_id'    => $plan->id,
                'installment_number' => $i,
                'amount'             => $data['installment_amount'],
                'amount_paid'        => 0,
                'due_date'           => $dueDate,
                'status'             => 'pending',
            ]);
            $dueDate = date('Y-m-d', strtotime($dueDate . " +{$data['frequency_days']} days"));
        }

        return response()->json($plan->load('installments'), 201);
    }

    public function show(PaymentPlan $plan)
    {
        return $plan->load(['patient', 'installments']);
    }

    public function update(Request $request, PaymentPlan $plan)
    {
        $data = $request->validate([
            'name'                => 'sometimes|string|max:255',
            'total_amount'        => 'sometimes|integer|min:1',
            'down_payment'        => 'sometimes|integer|min:0',
            'installment_amount'  => 'sometimes|integer|min:1',
            'installment_count'   => 'sometimes|integer|min:1|max:120',
            'frequency_days'      => 'sometimes|integer|in:7,30',
            'start_date'          => 'sometimes|date',
        ]);

        $plan->update($data);
        return $plan->load('installments');
    }

    public function overdue(Request $request)
    {
        $installments = PaymentPlanInstallment::with(['plan.patient'])
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'waived')
            ->where('due_date', '<', now()->toDateString())
            ->where('amount_paid', '<', DB::raw('amount'))
            ->get()
            ->map(function ($ins) {
                $daysOverdue = now()->diffInDays($ins->due_date);
                return [
                    'installment' => $ins,
                    'plan'        => $ins->plan,
                    'patient'     => $ins->plan->patient,
                    'days_overdue'=> $daysOverdue,
                ];
            });

        return response()->json($installments);
    }
}