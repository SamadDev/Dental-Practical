<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\HandlesDataTableQueries;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use HandlesDataTableQueries;

    private const SORTABLE = [
        'created_at'  => 'expenses.created_at',
        'amount'      => 'expenses.amount',
        'description' => 'expenses.description',
    ];

    public function index(Request $request): JsonResponse
    {
        $q = $this->expenseQuery($request);

        $this->applySort($q, $request, self::SORTABLE, 'created_at');

        $page = $q->paginate($this->perPage($request));

        // Sum across the whole filter set — the header total must not change
        // just because the user paged forward.
        $total = (int) $this->expenseQuery($request)->sum('amount');

        return response()->json([
            ...$page->toArray(),
            'totals' => ['amount' => $total],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'      => 'required|integer|min:1',
            'description' => 'required|string|max:500',
        ]);

        return response()->json(Expense::create($data), 201);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['ok' => true]);
    }

    /** Filtered base query — built twice (rows + total), so keep it pure. */
    private function expenseQuery(Request $request): Builder
    {
        $q = Expense::query();

        if ($s = trim((string) $request->query('search'))) {
            $q->where('expenses.description', 'like', "%{$s}%");
        }

        $this->applyDateRange($q, $request, 'expenses.created_at');
        $this->applyAmountRange($q, $request, 'expenses.amount', 'min_amount', 'max_amount');

        return $q;
    }
}
