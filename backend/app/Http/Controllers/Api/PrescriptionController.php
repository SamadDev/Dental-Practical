<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The "medicine written by the doctor" part of the patient record.
 *
 * Nested under a patient (the dialog is always opened from one patient), and
 * every row remembers which doctor/visit it came from so the record doubles
 * as a prescription history rather than a scratch pad.
 */
class PrescriptionController extends Controller
{
    public function index(Patient $patient): JsonResponse
    {
        return response()->json(
            $patient->prescriptions()
                ->with('prescriber:id,name')
                ->orderByDesc('created_at')
                ->get(),
        );
    }

    public function store(Request $request, Patient $patient): JsonResponse
    {
        $data = $this->validated($request);

        // Auto-attach the doctor when the author is a doctor account, so the
        // history stays attributable even though the dialog does not send it.
        $user = $request->user();
        $doctorId = $data['doctor_id'] ?? $user->doctorProfile?->id;

        $prescription = $patient->prescriptions()->create($data + [
            'doctor_id'  => $doctorId,
            'visit_id'   => $data['visit_id'] ?? $this->latestVisitId($patient),
            'created_by' => $user->id,
        ]);

        return response()->json($prescription->load('prescriber:id,name'), 201);
    }

    public function destroy(Prescription $prescription): JsonResponse
    {
        $prescription->delete();

        return response()->json(['ok' => true]);
    }

    /** Most recent visit, so a prescription written from the chart still links to one. */
    private function latestVisitId(Patient $patient): ?int
    {
        return $patient->visits()->orderByDesc('created_at')->value('id');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'medication'   => 'required|string|max:255',
            'dose'         => 'required|string|max:100',
            // Stored upper-case (OD/BID…) to match what the dialog sends; the
            // UI lower-cases before looking up its rx.* translation key.
            'frequency'    => 'required|in:OD,BID,TID,QID,PRN,od,bid,tid,qid,prn',
            'duration'     => 'nullable|string|max:100',
            'instructions' => 'nullable|in:before_meal,after_meal,with_meal',
            'notes'        => 'nullable|string|max:2000',
            'visit_id'     => 'nullable|integer|exists:visits,id',
            'doctor_id'    => 'nullable|integer|exists:doctors,id',
        ]);
    }
}
