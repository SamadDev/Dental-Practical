<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AqsatContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AqsatContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = AqsatContract::with('patient:id,name,phone');

        if ($pid = $request->query('patient_id')) {
            $q->where('patient_id', $pid);
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json($q->orderByDesc('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'treatment_name' => 'required|string|max:255',
            'total_amount'   => 'required|integer|min:1',
        ]);

        // remaining_balance starts equal to total_amount — both flat whole IQD.
        $data['remaining_balance'] = $data['total_amount'];
        $data['status']            = 'active';

        return response()->json(AqsatContract::create($data), 201);
    }

    public function show(AqsatContract $aqsatContract): JsonResponse
    {
        return response()->json($aqsatContract->load('patient', 'visits'));
    }

    public function update(Request $request, AqsatContract $aqsatContract): JsonResponse
    {
        $data = $request->validate([
            'treatment_name' => 'sometimes|string|max:255',
            'status'         => 'sometimes|in:active,completed,cancelled',
        ]);

        $aqsatContract->update($data);
        return response()->json($aqsatContract);
    }
}
