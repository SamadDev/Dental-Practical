<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\HandlesDataTableQueries;
use App\Http\Controllers\Controller;
use App\Models\AqsatContract;
use App\Models\CashFlowForecast;
use App\Models\Expense;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashFlowForecastController extends Controller
{
    use HandlesDataTableQueries;

    /** Sort key => qualified column, whitelisted for the manual-entries table. */
    private const SORTABLE = [
        'forecast_date' => 'cash_flow_forecasts.forecast_date',
        'type'          => 'cash_flow_forecasts.type',
        'description'   => 'cash_flow_forecasts.description',
        'amount'        => 'cash_flow_forecasts.amount',
        'status'        => 'cash_flow_forecasts.status',
        'created_at'    => 'cash_flow_forecasts.created_at',
    ];

    /**
     * Generate cash flow forecast from existing data + manual entries.
     * Returns daily breakdown for a date range.
     */
    public function forecast(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to',   now()->addMonths(3)->endOfMonth()->toDateString());
        $includeManual = $request->boolean('include_manual', true);

        // 1. Projected Aqsat inflows (remaining balances distributed over time)
        $aqsatInflows = $this->projectAqsatInflows($from, $to);

        // 2. Projected visit inflows (patients with upcoming appointments)
        $visitInflows = $this->projectVisitInflows($from, $to);

        // 3. Projected expense outflows (recurring expenses pattern)
        $expenseOutflows = $this->projectExpenseOutflows($from, $to);

        // 4. Manual forecasts
        $manualItems = $includeManual ? $this->manualItems($from, $to) : [];

        // Combine all into daily buckets
        $daily = $this->aggregateDaily($from, $to, $aqsatInflows, $visitInflows, $expenseOutflows, $manualItems);

        // Summary totals
        $totals = [
            'total_inflow'  => array_sum(array_column($daily, 'inflow')),
            'total_outflow' => array_sum(array_column($daily, 'outflow')),
            'net'           => array_sum(array_column($daily, 'net')),
            'running_balance' => $this->runningBalance($daily),
        ];

        return response()->json([
            'range'   => ['from' => $from, 'to' => $to],
            'daily'   => $daily,
            'totals'  => $totals,
            'currency' => 'IQD',
        ]);
    }

    /**
     * Simple weekly buckets for charting.
     */
    public function weekly(Request $request): JsonResponse
    {
        // Re-run the forecast pipeline directly (a JsonResponse has no ->json(),
        // so we cannot decode forecast()'s response — share the logic instead).
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to',   now()->addMonths(3)->endOfMonth()->toDateString());
        $includeManual = $request->boolean('include_manual', true);

        $daily = $this->aggregateDaily(
            $from,
            $to,
            $this->projectAqsatInflows($from, $to),
            $this->projectVisitInflows($from, $to),
            $this->projectExpenseOutflows($from, $to),
            $includeManual
                ? $this->manualItems($from, $to)
                : [],
        );

        $weekly = [];
        foreach ($daily as $day) {
            $weekStart = (new \DateTime($day['date']))->modify('monday this week')->format('Y-m-d');
            if (!isset($weekly[$weekStart])) {
                $weekly[$weekStart] = ['week_start' => $weekStart, 'inflow' => 0, 'outflow' => 0, 'net' => 0];
            }
            $weekly[$weekStart]['inflow']  += $day['inflow'];
            $weekly[$weekStart]['outflow'] += $day['outflow'];
            $weekly[$weekStart]['net']     += $day['net'];
        }

        return response()->json([
            'weekly' => array_values($weekly),
            'currency' => 'IQD',
        ]);
    }

    /**
     * Manual forecast entries, in the shape the frontend DataTable expects
     * (search / sort / per_page + `totals` summed over the whole filter set,
     * not just the current page — same contract as /expenses).
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->manualEntries($request);
        $this->applySort($query, $request, self::SORTABLE, 'forecast_date', 'asc');

        $page = $query->paginate($this->perPage($request));

        $sums = $this->manualEntries($request)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END), 0) as inflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END), 0) as outflow")
            ->first();

        $inflow  = (int) ($sums->inflow ?? 0);
        $outflow = (int) ($sums->outflow ?? 0);

        return response()->json([
            ...$page->toArray(),
            'totals' => [
                'inflow'  => $inflow,
                'outflow' => $outflow,
                'net'     => $inflow - $outflow,
            ],
        ]);
    }

    /** The filtered manual-entry query, shared by the list and its totals. */
    private function manualEntries(Request $request): Builder
    {
        $query = CashFlowForecast::query();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where('description', 'like', "%{$search}%");
        }

        return $this->applyDateRange($query, $request, 'forecast_date');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'forecast_date' => 'required|date',
            'type'          => 'required|in:inflow,outflow',
            'source'        => 'required|string|max:50',
            'source_id'     => 'nullable|integer',
            'description'   => 'required|string|max:255',
            'amount'        => 'required|integer|min:1',
            'status'        => 'sometimes|in:projected,confirmed,cancelled',
        ]);
        $data['status'] = $data['status'] ?? 'projected';

        return response()->json(CashFlowForecast::create($data), 201);
    }

    public function update(Request $request, CashFlowForecast $forecast): JsonResponse
    {
        $data = $request->validate([
            'forecast_date' => 'sometimes|date',
            'type'          => 'sometimes|in:inflow,outflow',
            'source'        => 'sometimes|string|max:50',
            'source_id'     => 'nullable|integer',
            'description'   => 'sometimes|string|max:255',
            'amount'        => 'sometimes|integer|min:1',
            'status'        => 'sometimes|in:projected,confirmed,cancelled',
        ]);

        $forecast->update($data);
        return response()->json($forecast);
    }

    public function destroy(CashFlowForecast $forecast): JsonResponse
    {
        $forecast->delete();
        return response()->json(['ok' => true]);
    }

    /** Auto-generate forecast from Aqsat contracts. */
    public function generateFromAqsat(Request $request): JsonResponse
    {
        $months = (int) $request->query('months', 3);
        $from   = now()->startOfMonth();
        $to     = now()->addMonths($months)->endOfMonth();

        // Clear existing auto-generated Aqsat forecasts in range
        CashFlowForecast::where('source', 'aqsat')
            ->whereBetween('forecast_date', [$from, $to])
            ->delete();

        $contracts = AqsatContract::where('status', 'active')
            ->where('remaining_balance', '>', 0)
            ->get();

        $created = 0;
        foreach ($contracts as $contract) {
            // Distribute remaining balance evenly over remaining months
            $monthsLeft = max(1, $contract->remaining_balance > 0 ? ceil($contract->remaining_balance / 100000) : 1); // rough heuristic
            $perMonth   = (int) round($contract->remaining_balance / $monthsLeft);

            for ($i = 0; $i < $monthsLeft; $i++) {
                $date = $from->copy()->addMonths($i);
                if ($date->gt($to)) break;

                CashFlowForecast::create([
                    'forecast_date' => $date->toDateString(),
                    'type'          => 'inflow',
                    'source'        => 'aqsat',
                    'source_id'     => $contract->id,
                    'description'   => "Aqsat: {$contract->treatment_name} ({$contract->patient->name})",
                    'amount'        => min($perMonth, $contract->remaining_balance),
                    'status'        => 'projected',
                ]);
                $created++;
            }
        }

        return response()->json(['created' => $created]);
    }

    // ---------- Private projection helpers ----------

    /**
     * Manual entries in the shape aggregateDaily() expects.
     *
     * The model casts `forecast_date` to a date, so toArray() would hand back an
     * ISO timestamp ("2026-09-29T00:00:00.000000Z"). That never matches a daily
     * bucket key ("2026-09-29"), which silently dropped every manual entry from
     * the projection — the rows were stored, validated and listed, but never
     * counted. Dates are narrowed to Y-m-d here.
     *
     * @return list<array<string, mixed>>
     */
    private function manualItems(string $from, string $to): array
    {
        return CashFlowForecast::whereBetween('forecast_date', [$from, $to])
            ->orderBy('forecast_date')
            ->get()
            ->map(fn (CashFlowForecast $f) => [
                'date'        => $f->forecast_date->toDateString(),
                'type'        => $f->type,
                'source'      => $f->source,
                'source_id'   => $f->source_id,
                'description' => $f->description,
                'amount'      => $f->amount,
                'status'      => $f->status,
            ])
            ->all();
    }

    private function projectAqsatInflows(string $from, string $to): array
    {
        return AqsatContract::where('status', 'active')
            ->where('remaining_balance', '>', 0)
            ->get()
            ->flatMap(function ($c) use ($from, $to) {
                $monthsLeft = max(1, now()->diffInMonths(\Carbon\Carbon::parse($to)) + 1);
                $perMonth   = (int) round($c->remaining_balance / $monthsLeft);
                $items = [];
                for ($i = 0; $i < $monthsLeft; $i++) {
                    $date = (new \DateTime($from))->modify("+{$i} months")->format('Y-m-d');
                    if ($date > $to) break;
                    $items[] = [
                        'date'        => $date,
                        'type'        => 'inflow',
                        'source'      => 'aqsat',
                        'source_id'   => $c->id,
                        'description' => "Aqsat: {$c->treatment_name} ({$c->patient->name})",
                        'amount'      => min($perMonth, $c->remaining_balance),
                        'status'      => 'projected',
                    ];
                }
                return $items;
            })
            ->toArray();
    }

    private function projectVisitInflows(string $from, string $to): array
    {
        // Patients with upcoming appointments but no recent visit
        return Visit::whereIn('queue_status', ['pending', 'active'])
            ->whereBetween('created_at', [$from, $to])
            ->with('patient')
            ->get()
            ->map(function ($v) {
                return [
                    'date'        => $v->created_at->toDateString(),
                    'type'        => 'inflow',
                    'source'      => 'visit',
                    'source_id'   => $v->id,
                    'description' => "Visit: {$v->patient->name}",
                    'amount'      => $v->total_cost ?? 0,
                    'status'      => 'projected',
                ];
            })
            ->toArray();
    }

    private function projectExpenseOutflows(string $from, string $to): array
    {
        // Average daily expenses from last 30 days projected forward
        $avgDaily = Expense::where('created_at', '>=', now()->subDays(30))
            ->avg('amount') ?? 0;

        if ($avgDaily <= 0) return [];

        $items = [];
        $start = new \DateTime($from);
        $end   = new \DateTime($to);
        while ($start <= $end) {
            $items[] = [
                'date'        => $start->format('Y-m-d'),
                'type'        => 'outflow',
                'source'      => 'expense',
                'source_id'   => null,
                'description' => 'Projected daily expenses (30-day avg)',
                'amount'      => (int) round($avgDaily),
                'status'      => 'projected',
            ];
            $start->modify('+1 day');
        }
        return $items;
    }

    private function aggregateDaily(string $from, string $to, array ...$sources): array
    {
        $daily = [];
        $start = new \DateTime($from);
        $end   = new \DateTime($to);

        // Initialize all days
        while ($start <= $end) {
            $d = $start->format('Y-m-d');
            $daily[$d] = ['date' => $d, 'inflow' => 0, 'outflow' => 0, 'net' => 0, 'items' => []];
            $start->modify('+1 day');
        }

        // Aggregate all sources
        foreach ($sources as $source) {
            foreach ($source as $item) {
                // Manual forecast rows (from the cash_flow_forecasts table) carry
                // their date as `forecast_date`; projection helpers use `date`.
                $d = $item['date'] ?? $item['forecast_date'] ?? null;
                if ($d === null) continue;
                // Narrow ISO timestamps to Y-m-d so a row can never be silently
                // skipped just because it was serialized with a time component.
                $d = substr((string) $d, 0, 10);
                if (!isset($daily[$d])) continue;
                if ($item['type'] === 'inflow') $daily[$d]['inflow'] += $item['amount'];
                else $daily[$d]['outflow'] += $item['amount'];
                $daily[$d]['net'] = $daily[$d]['inflow'] - $daily[$d]['outflow'];
                $daily[$d]['items'][] = $item;
            }
        }

        return array_values($daily);
    }

    private function runningBalance(array $daily): array
    {
        $balance = 0;
        $result = [];
        foreach ($daily as $day) {
            $balance += $day['net'];
            $result[] = ['date' => $day['date'], 'balance' => $balance];
        }
        return $result;
    }
}