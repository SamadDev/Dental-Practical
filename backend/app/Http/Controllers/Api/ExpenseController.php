<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Expense::query();

        if ($from = $request->query('from')) $q->whereDate('created_at', '>=', $from);
        if ($to   = $request->query('to'))   $q->whereDate('created_at', '<=', $to);

        return response()->json($q->orderByDesc('created_at')->get());
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
}
