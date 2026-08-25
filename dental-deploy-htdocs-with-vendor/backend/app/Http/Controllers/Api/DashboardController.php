<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AqsatContract;
use App\Models\Expense;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Real-time financial metrics.
     * Pure integer arithmetic — every sum is cast to int so JSON never emits a float.
     */
    public function metrics(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $visitsQ   = Visit::query();
        $expensesQ = Expense::query();

        if ($from) {
            $visitsQ->whereDate('created_at', '>=', $from);
            $expensesQ->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $visitsQ->whereDate('created_at', '<=', $to);
            $expensesQ->whereDate('created_at', '<=', $to);
        }

        $totalCashCollected = (int) (clone $visitsQ)->sum('amount_paid');
        $activeCustomerDebt = (int) (clone $visitsQ)->sum('short_term_debt');
        $totalExpenses      = (int) (clone $expensesQ)->sum('amount');

        // Upcoming Aqsat revenue is not date-filtered — it's a forward-looking pipeline.
        $upcomingAqsatRevenue = (int) AqsatContract::where('status', 'active')
            ->sum('remaining_balance');

        return response()->json([
            'total_cash_collected'   => $totalCashCollected,
            'active_customer_debt'   => $activeCustomerDebt,
            'upcoming_aqsat_revenue' => $upcomingAqsatRevenue,
            'total_expenses'         => $totalExpenses,
            'true_net_profit'        => $totalCashCollected - $totalExpenses,
            'currency'               => 'IQD',
            'range'                  => ['from' => $from, 'to' => $to],
        ]);
    }
}
