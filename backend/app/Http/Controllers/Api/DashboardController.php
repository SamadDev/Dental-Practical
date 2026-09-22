<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AqsatContract;
use App\Models\Expense;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Real-time financial metrics.
     * Pure integer arithmetic — every sum is cast to int so JSON never emits a float.
     */
    public function metrics(Request $request): JsonResponse
    {
        [$from, $to, $granularity] = $this->resolveRange($request);

        // Revenue and debt only come from *completed* visits — pending/active
        // queue rows carry no money yet, and counting them inflated both KPIs
        // (e.g. paid-in cash larger than anything actually collected).
        $completed = $this->inRange(Visit::query(), $from, $to)->where('queue_status', 'completed');

        $current  = $this->kpis($from, $to);
        $previous = ($prev = $this->previousWindow($from, $to)) ? $this->kpis($prev[0], $prev[1]) : [];

        // Forward-looking pipelines are deliberately not date-filtered.
        $upcomingAqsatRevenue = (int) AqsatContract::where('status', 'active')->sum('remaining_balance');
        $aqsatPaid = (int) (clone $completed)->whereNotNull('aqsat_contract_id')->sum('amount_paid');

        $totalPatients = (int) Patient::count();
        $totalAppointments = (int) Patient::whereNotNull('appointment_date')
            ->where('appointment_date', '!=', '')
            ->where('appointment_date', '>=', Carbon::now()->startOfDay()->format('Y-m-d'))
            ->count();
        $totalTreatments = (int) (clone $completed)
            ->whereNotNull('treatment_name')
            ->where('treatment_name', '!=', '')
            ->distinct()
            ->count('treatment_name');

        $revenue  = $this->series(clone $completed, 'amount_paid', $from, $to, $granularity);
        $expenses = $this->series($this->inRange(Expense::query(), $from, $to), 'amount', $from, $to, $granularity);
        $patients = $this->genderBreakdown();

        return response()->json([
            'total_cash_collected'   => $current['total_cash_collected'],
            'active_customer_debt'   => $current['active_customer_debt'],
            'upcoming_aqsat_revenue' => $upcomingAqsatRevenue,
            'total_expenses'         => $current['total_expenses'],
            'true_net_profit'        => $current['true_net_profit'],

            'total_patients'         => $totalPatients,
            'total_appointments'     => $totalAppointments,
            'completed_visits'       => $current['completed_visits'],
            'total_treatments'       => $totalTreatments,
            'aqsat_paid'             => $aqsatPaid,
            'aqsat_pending'          => $upcomingAqsatRevenue,

            'revenue_data'    => $revenue['data'],
            'revenue_labels'  => $revenue['labels'],
            'revenue_keys'    => $revenue['keys'],
            'expenses_data'   => $expenses['data'],
            'expenses_labels' => $expenses['labels'],
            'expenses_keys'   => $expenses['keys'],
            'patients_data'   => $patients['data'],
            'patients_labels' => $patients['labels'],
            'patients_keys'   => $patients['keys'],

            'previous_metrics' => $previous,
            'granularity'      => $granularity,
            'currency'         => 'IQD',
            'range'            => ['from' => $from, 'to' => $to],
        ]);
    }

    /**
     * Resolve the window the dashboard should report on.
     *
     * The UI sends ?days=7|30|90 (its range tabs) which this endpoint used to
     * ignore completely; explicit from/to still wins when provided.
     *
     * @return array{0: ?string, 1: ?string, 2: string} [from, to, granularity]
     */
    private function resolveRange(Request $request): array
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $days = (int) $request->query('days', 0);

        if (! $from && ! $to) {
            $days = $days > 0 ? min($days, 3650) : 30;
            $to   = Carbon::now()->toDateString();
            $from = Carbon::now()->subDays($days - 1)->toDateString();
        }

        if ($from && $to && Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        $span = ($from && $to)
            ? Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1
            : 30;

        // Long windows are charted monthly so the series stays readable.
        return [$from, $to, $span > 92 ? 'month' : 'day'];
    }

    /** Apply the reporting window to a query on $column. */
    private function inRange(Builder $query, ?string $from, ?string $to, string $column = 'created_at'): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn ($q) => $q->whereDate($column, '<=', $to));
    }

    /**
     * The five headline KPIs for a window, so the same maths is reused for the
     * previous period (the UI renders a delta on each stat card).
     *
     * @return array<string, int>
     */
    private function kpis(?string $from, ?string $to): array
    {
        $completed = $this->inRange(Visit::query(), $from, $to)->where('queue_status', 'completed');
        $expenses  = $this->inRange(Expense::query(), $from, $to);

        $cash  = (int) (clone $completed)->sum('amount_paid');
        $spent = (int) (clone $expenses)->sum('amount');

        return [
            'total_cash_collected' => $cash,
            'active_customer_debt' => (int) (clone $completed)->sum('short_term_debt'),
            'total_expenses'       => $spent,
            'true_net_profit'      => $cash - $spent,
            'completed_visits'     => (int) (clone $completed)->count(),
        ];
    }

    /**
     * The window of equal length immediately before [$from, $to].
     *
     * @return array{0: string, 1: string}|null
     */
    private function previousWindow(?string $from, ?string $to): ?array
    {
        if (! $from || ! $to) {
            return null;
        }

        $start = Carbon::parse($from);
        $span  = $start->diffInDays(Carbon::parse($to)) + 1;

        if ($span < 1) {
            return null;
        }

        $prevEnd   = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($span - 1);

        return [$prevStart->toDateString(), $prevEnd->toDateString()];
    }

    /**
     * Bucket a money column into a continuous daily/monthly series.
     *
     * Buckets are derived from the raw column value so the keys keep the
     * database's own timezone: SQLite here stores local time, and casting the
     * value to Carbon first would push rows near midnight into the wrong day.
     *
     * @return array{keys: list<string>, labels: list<string>, data: list<int>}
     */
    private function series(Builder $query, string $sumColumn, ?string $from, ?string $to, string $granularity): array
    {
        $length  = $granularity === 'month' ? 7 : 10;
        $buckets = [];

        foreach ($query->toBase()->get(['created_at', $sumColumn]) as $row) {
            $key = substr((string) $row->created_at, 0, $length);
            $buckets[$key] = ($buckets[$key] ?? 0) + (int) $row->{$sumColumn};
        }

        $keys = [];

        if ($from && $to) {
            $cursor = $granularity === 'month'
                ? Carbon::parse($from)->startOfMonth()
                : Carbon::parse($from);
            $end = $granularity === 'month'
                ? Carbon::parse($to)->startOfMonth()
                : Carbon::parse($to);

            $guard = 0;
            while ($cursor->lte($end) && $guard++ < 400) {
                $keys[] = $granularity === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
                $cursor = $granularity === 'month' ? $cursor->copy()->addMonth() : $cursor->copy()->addDay();
            }
        } else {
            $keys = array_keys($buckets);
            sort($keys);
        }

        return [
            'keys'   => $keys,
            // English fallback only — the UI re-localises using `keys`.
            'labels' => array_map(
                fn ($k) => $granularity === 'month'
                    ? Carbon::parse($k . '-01')->format('M Y')
                    : Carbon::parse($k)->format('M j'),
                $keys
            ),
            'data'   => array_map(fn ($k) => (int) ($buckets[$k] ?? 0), $keys),
        ];
    }

    /**
     * Patient mix by gender. Empty slices are dropped so the donut never
     * renders a zero-value segment.
     *
     * @return array{keys: list<string>, labels: list<string>, data: list<int>}
     */
    private function genderBreakdown(): array
    {
        $rows = Patient::query()
            ->selectRaw("COALESCE(NULLIF(gender, ''), 'unknown') AS gender_key, COUNT(*) AS total")
            ->groupBy('gender_key')
            ->pluck('total', 'gender_key');

        $order  = ['male', 'female', 'unknown'];
        $keys   = [];
        $labels = [];
        $data   = [];

        foreach ($order as $key) {
            if (isset($rows[$key])) {
                $keys[]   = $key;
                $labels[] = ucfirst($key);
                $data[]   = (int) $rows[$key];
            }
        }

        foreach ($rows as $key => $total) {
            if (! in_array($key, $order, true)) {
                $keys[]   = $key;
                $labels[] = ucfirst((string) $key);
                $data[]   = (int) $total;
            }
        }

        return ['keys' => $keys, 'labels' => $labels, 'data' => $data];
    }
}
