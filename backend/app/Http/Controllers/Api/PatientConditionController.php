<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientConditionController extends Controller
{
    /** Severe first, then moderate, then mild — the UI displays them in this order. */
    public function index(Patient $patient): JsonResponse
    {
        return response()->json(
            $patient->conditions()->orderByRaw(
                "CASE severity WHEN 'severe' THEN 0 WHEN 'moderate' THEN 1 ELSE 2 END",
            )->orderBy('name')->get(),
        );
    }

    public function store(Request $request, Patient $patient): JsonResponse
    {
        return response()->json($patient->conditions()->create($this->validated($request)), 201);
    }

    public function update(Request $request, PatientCondition $condition): JsonResponse
    {
        $condition->update($this->validated($request));

        return response()->json($condition);
    }

    public function destroy(PatientCondition $condition): JsonResponse
    {
        $condition->delete();

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type'     => 'required|in:allergy,condition',
            'name'     => 'required|string|max:255',
            'severity' => 'required|in:mild,moderate,severe',
            'note'     => 'nullable|string|max:2000',
        ]);
    }
}