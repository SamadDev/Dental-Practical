<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\ToothRecordHistory;
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

    /**
     * Treatment history for one tooth — newest first. Falls back to the
     * current record so teeth recorded before the history log existed
     * still show their (single) known state instead of nothing.
     */
    public function history(Patient $patient, int $tooth): JsonResponse
    {
        $entries = ToothRecordHistory::query()
            ->where('patient_id', $patient->id)
            ->where('tooth_number', $tooth)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ToothRecordHistory $row) => $this->historyEntry(
                $row->id,
                $row->status,
                $row->note,
                $row->surfaces,
                $row->created_at,
            ));

        if ($entries->isEmpty()) {
            $current = $patient->toothRecords()->where('tooth_number', $tooth)->first();

            if ($current) {
                $entries->push($this->historyEntry(
                    $current->id,
                    $current->status,
                    $current->note,
                    $current->surfaces,
                    $current->updated_at,
                ));
            }
        }

        return response()->json($entries);
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        $data = $request->validate([
            'teeth'                => 'present|array|max:32',
            'teeth.*.tooth_number' => 'required|integer|between:1,32',
            'teeth.*.status'       => 'required|in:'.implode(',', self::STATUSES),
            'teeth.*.note'         => 'nullable|string|max:500',
            'teeth.*.surfaces'     => 'nullable|array|max:6',
            'teeth.*.surfaces.*'   => 'string|max:3',
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

            $existing = $patient->toothRecords()->get()->keyBy('tooth_number');

            foreach ($teeth as $tooth) {
                $surfaces = $tooth['surfaces'] ?? [];
                $record = $patient->toothRecords()->updateOrCreate(
                    ['tooth_number' => $tooth['tooth_number']],
                    ['status' => $tooth['status'], 'note' => $tooth['note'] ?? null, 'surfaces' => $surfaces],
                );

                // Log only meaningful changes so history is an audit trail,
                // not a copy of every save.
                $previous = $existing->get($tooth['tooth_number']);
                if (! $previous
                    || $previous->status !== $record->status
                    || (string) ($previous->note ?? '') !== (string) ($record->note ?? '')
                    || $this->normSurfaces($previous->surfaces) !== $this->normSurfaces($record->surfaces)
                ) {
                    $patient->toothHistory()->create([
                        'tooth_number' => $record->tooth_number,
                        'status'       => $record->status,
                        'note'         => $record->note,
                        'surfaces'     => $record->surfaces,
                    ]);
                }
            }
        });

        return response()->json($this->chart($patient));
    }

    private function chart(Patient $patient): array
    {
        return $patient->toothRecords()
            ->orderBy('tooth_number')
            ->get(['tooth_number', 'status', 'note', 'surfaces'])
            ->all();
    }

    /** Shape the history panel expects: procedure text falls back to status. */
    private function historyEntry(int $id, string $status, ?string $note, ?array $surfaces, $createdAt): array
    {
        return [
            'id'         => $id,
            'status'     => $status,
            'procedure'  => $note ?: null,
            'surfaces'   => $surfaces ?? [],
            'created_at' => $createdAt,
        ];
    }

    /** Order-insensitive comparison key for surface lists. */
    private function normSurfaces(?array $surfaces): string
    {
        $list = array_values($surfaces ?? []);
        sort($list);

        return json_encode($list);
    }
}
