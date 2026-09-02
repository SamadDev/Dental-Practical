<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dental chart — per-tooth statuses (universal numbering 1–32).
 * The client sends the FULL chart each time; rows missing from the
 * payload are deleted, i.e. "healthy" teeth have no record.
 */
class ToothChartController extends Controller
{
    public const STATUSES = ['cavity', 'filled', 'crown', 'root_canal', 'missing', 'implant', 'previous_visit'];

    public function show(Patient $patient): JsonResponse
    {
        return response()->json($this->chart($patient));
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        $data = $request->validate([
            'teeth'                => 'present|array|max:32',
            'teeth.*.tooth_number' => 'required|integer|between:1,32',
            'teeth.*.status'       => 'required|in:'.implode(',', self::STATUSES),
            'teeth.*.note'         => 'nullable|string|max:500',
        ]);

        $teeth = collect($data['teeth'] ?? []);
        abort_if(
            $teeth->pluck('tooth_number')->duplicates()->isNotEmpty(),
            422,
            'Duplicate tooth numbers in payload.',
        );

        DB::transaction(function () use ($patient, $teeth) {
            $patient->toothRecords()
                ->whereNotIn('tooth_number', $teeth->pluck('tooth_number')->all())
                ->delete();

            foreach ($teeth as $tooth) {
                $patient->toothRecords()->updateOrCreate(
                    ['tooth_number' => $tooth['tooth_number']],
                    ['status' => $tooth['status'], 'note' => $tooth['note'] ?? null],
                );
            }
        });

        return response()->json($this->chart($patient));
    }

    private function chart(Patient $patient): array
    {
        return $patient->toothRecords()
            ->orderBy('tooth_number')
            ->get(['tooth_number', 'status', 'note'])
            ->all();
    }
}
