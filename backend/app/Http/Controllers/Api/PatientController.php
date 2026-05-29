<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Patient::query();

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->boolean('has_debt')) {
            $q->whereHas('visits', fn ($v) => $v->where('short_term_debt', '>', 0));
        }

        $page = $q->orderByDesc('id')->paginate((int) $request->query('per_page', 25));

        // Expose the patient's running debt total so the frontend can suppress
        // the "Add Walk-In" button while money is still owed.
        $page->getCollection()->each->append('outstanding_short_term_debt');

        return response()->json($page);
    }

    public function show(Patient $patient): JsonResponse
    {
        return response()->json(
            $patient->load([
                'visits' => fn ($q) => $q->orderByDesc('created_at'),
                'aqsatContracts',
            ])->append('outstanding_short_term_debt')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'nullable|string|max:50',
            'age'              => 'nullable|integer|min:0|max:150',
            'appointment_date' => 'nullable|date',
            'medical_notes'    => 'nullable|string',
        ]);

        return response()->json(Patient::create($data), 201);
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'phone'            => 'nullable|string|max:50',
            'age'              => 'nullable|integer|min:0|max:150',
            'appointment_date' => 'nullable|date',
            'medical_notes'    => 'nullable|string',
        ]);

        $patient->update($data);

        return response()->json($patient);
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $patient->delete();
        return response()->json(['ok' => true]);
    }
}
