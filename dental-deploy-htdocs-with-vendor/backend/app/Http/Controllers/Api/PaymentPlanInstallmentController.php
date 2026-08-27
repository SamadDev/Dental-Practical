<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlanInstallment;
use Illuminate\Http\Request;

class PaymentPlanInstallmentController extends Controller
{
    public function pay(Request $request, PaymentPlanInstallment $installment)
    {
        $data = $request->validate([
            'amount'     => 'required|integer|min:1',
            'paid_date'  => 'nullable|date',
        ]);

        $newPaid = $installment->amount_paid + $data['amount'];
        $status = $newPaid >= $installment->amount ? 'paid' : 'partial';

        $installment->update([
            'amount_paid' => $newPaid,
            'paid_date'   => $data['paid_date'] ?? now()->toDateString(),
            'status'      => $status,
        ]);

        // Update plan status if all installments paid
        $plan = $installment->plan;
        $allPaid = $plan->installments()->where('status', '!=', 'paid')->where('status', '!=', 'waived')->count() === 0;
        if ($allPaid) {
            $plan->update(['status' => 'completed']);
        }

        return response()->json($installment->fresh());
    }

    public function waive(PaymentPlanInstallment $installment)
    {
        $installment->update([
            'status'       => 'waived',
            'amount_paid'  => $installment->amount,
            'paid_date'    => now()->toDateString(),
        ]);

        // Update plan status if all installments paid/waived
        $plan = $installment->plan;
        $allDone = $plan->installments()->where('status', '!=', 'paid')->where('status', '!=', 'waived')->count() === 0;
        if ($allDone) {
            $plan->update(['status' => 'completed']);
        }

        return response()->json($installment->fresh());
    }
}