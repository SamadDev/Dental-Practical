<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Expense;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Clinic performance reports: how much each treatment earned, what each
 * doctor produced, and the collected-vs-expenses trend — for a date window.
 *
 * Same accounting rules as the dashboard: money only comes from *completed*
 * visits, every sum is an int (whole IQD), and long windows are bucketed
 * monthly so the chart stays readable.
 */
class ReportController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        [$from, $to, $granularity] = $this->resolveRange($request);

        $completed = $this->inRange(Visit::query(), $from, $to)
            ->where('queue_status', 'completed');

        $expensesQuery = $this->inRange(Expense::query(), $from, $to);

        $charged   = (int) (clone $completed)->sum('total_cost');
        $collected = (int) (clone $completed)->sum('amount_paid');
        $spent     = (int) (clone $expensesQuery)->sum('amount');

        return response()->json([
            'range' => [
                'from'        => $from,
                'to'          => $to,
                'granularity' => $granularity,
            ],
            'kpis' => [
                'visits'      => (int) (clone $completed)->count(),
                'charged'     => $charged,
                'collected'   => $collected,
                'outstanding' => $charged - $collected,
                'expenses'    => $spent,
                'net'         => $collected - $spent,
            ],
            'by_treatment' => $this->byTreatment($completed),
            'by_doctor'    => $this->byDoctor($completed),
            'trend'        => $this->trend($completed, $expensesQuery, $from, $to, $granularity),
        ]);
    }

    /**
     * Revenue grouped by treatment name — the "what earns the clinic money"
     * table. Blank names collapse into one null row the UI labels itself.
     *
     * @return list<array{treatment: ?string, visits: int, charged: int, collected: int}>
     */
    private function byTreatment(Builder $completed): array
    {
        $rows = (clone $completed)
            ->selectRaw(
                "NULLIF(TRIM(COALESCE(treatment_name, '')), '') AS tkey,
                 COUNT(*) AS visits,
                 COALESCE(SUM(total_cost), 0)   AS charged,
                 COALESCE(SUM(amount_paid), 0)  AS collected"
            )
            ->groupBy('tkey')
            ->orderByDesc('collected')
            ->get();

        return $rows->map(fn ($row) => [
            'treatment' => $row->tkey,
            'visits'    => (int) $row->visits,
            'charged'   => (int) $row->charged,
            'collected' => (int) $row->collected,
        ])->all();
    }

    /**
     * Production grouped by the doctor who performed the visit.
     * Visits without a doctor come back as doctor_id null (UI: "Unassigned").
     *
     * @return list<array{doctor_id: ?int, name: ?string, visits: int, charged: int, collected: int}>
     */
    private function byDoctor(Builder $completed): array
    {
        $rows = (clone $completed)
            ->selectRaw(
                "COALESCE(doctor_id, 0) AS dkey,
                 COUNT(*) AS visits,
                 COALESCE(SUM(total_cost), 0)   AS charged,
                 COALESCE(SUM(amount_paid), 0)  AS collected"
            )
            ->groupBy('dkey')
            ->orderByDesc('collected')
            ->get();

        $names = Doctor::query()
            ->with('user:id,name')
            ->get()
            ->mapWithKeys(fn ($doctor) => [$doctor->id => $doctor->name]);

        return $rows->map(fn ($row) => [
            'doctor_id' => ((int) $row->dkey) ?: null,
            'name'      => $row->dkey ? ($names[$row->dkey] ?? null) : null,
            'visits'    => (int) $row->visits,
            'charged'   => (int) $row->charged,
            'collected' => (int) $row->collected,
        ])->all();
    }

    /**
     * Collected vs expenses over the window, bucketed day or month.
     * Buckets come from the raw column values (same as the dashboard) so rows
     * near midnight stay in the database's own timezone.
     *
     * @return array{keys: list<string>, labels: list<string>, collected: list<int>, expenses: list<int>}
     */
    private function trend(
        Builder $completed,
        Builder $expensesQuery,
        ?string $from,
        ?string $to,
        string $granularity,
    ): array {
        $length    = $granularity === 'month' ? 7 : 10;
        $collected = [];
        $expenses  = [];

        foreach ($completed->toBase()->get(['created_at', 'amount_paid']) as $row) {
            $key = substr((string) $row->created_at, 0, $length);
            $collected[$key] = ($collected[$key] ?? 0) + (int) $row->amount_paid;
        }

        foreach ($expensesQuery->toBase()->get(['created_at', 'amount']) as $row) {
            $key = substr((string) $row->created_at, 0, $length);
            $expenses[$key] = ($expenses[$key] ?? 0) + (int) $row->amount;
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
            $keys = array_keys($collected + $expenses);
            sort($keys);
        }

        return [
            'keys'      => $keys,
            // English fallback only — the UI re-localises using `keys`.
            'labels'    => array_map(
                fn ($k) => $granularity === 'month'
                    ? Carbon::parse($k . '-01')->format('M Y')
                    : Carbon::parse($k)->format('M j'),
                $keys,
            ),
            'collected' => array_map(fn ($k) => (int) ($collected[$k] ?? 0), $keys),
            'expenses'  => array_map(fn ($k) => (int) ($expenses[$k] ?? 0), $keys),
        ];
    }

    /**
     * Mirror of DashboardController::resolveRange — ?days=N shortcut plus
     * from/to swap and the >92-day monthly bucketing rule.
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

        return [$from, $to, $span > 92 ? 'month' : 'day'];
    }

    /** Apply the reporting window to a query on created_at. */
    private function inRange(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }
}
