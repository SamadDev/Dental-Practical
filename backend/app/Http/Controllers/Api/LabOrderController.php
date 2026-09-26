<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\HandlesDataTableQueries;
use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Work sent out of the clinic (prosthetic orders + diagnostic tests).
 * Same hand-off as the printed sheet: pending -> in_lab -> ready -> delivered
 * (or cancelled). The frontend list, the patient-page dialog and the printed
 * lab report all read from these endpoints.
 */
class LabOrderController extends Controller
{
    use HandlesDataTableQueries;

    private const SORTABLE = [
        'requested_at' => 'lab_orders.requested_at',
        'due_date'     => 'lab_orders.due_date',
        'cost'         => 'lab_orders.cost',
        'status'       => 'lab_orders.status',
        'created_at'   => 'lab_orders.created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $q = $this->labQuery($request);

        $this->applySort($q, $request, self::SORTABLE, 'requested_at');

        $page = $q->paginate($this->perPage($request, 50));

        // The views read `data.data` for rows and `data.meta` for the pager.
        return response()->json([
            ...$page->toArray(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
                'per_page'     => $page->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $order = LabOrder::create([
            ...$data,
            'category'     => $data['category'] ?? 'prosthetic',
            'status'       => 'pending',
            'requested_at' => now(),
            'created_by'   => $request->user()?->id,
        ]);

        return response()->json($order->load('patient'), 201);
    }

    public function show(LabOrder $labOrder): JsonResponse
    {
        return response()->json($labOrder->load(['patient', 'doctor', 'visit']));
    }

    public function update(Request $request, LabOrder $labOrder): JsonResponse
    {
        $data = $request->validate($this->rules(partial: true));

        // Stamp (or unstamp) completion the same way the printed sheet does.
        if (array_key_exists('status', $data)) {
            $data['completed_at'] = in_array($data['status'], ['delivered', 'cancelled'], true)
                ? ($labOrder->completed_at ?? now())
                : null;
        }

        $labOrder->update($data);

        return response()->json($labOrder->load('patient'));
    }

    public function destroy(LabOrder $labOrder): JsonResponse
    {
        $labOrder->delete();

        return response()->json(['ok' => true]);
    }

    /** Filtered base query (rows + search) — keep pure so it can be reused. */
    private function labQuery(Request $request): Builder
    {
        $q = LabOrder::query()->with('patient:id,name');

        if ($patientId = (int) $request->query('patient_id')) {
            $q->where('patient_id', $patientId);
        }

        if ($status = trim((string) $request->query('status'))) {
            $q->where('status', $status);
        }

        if ($category = trim((string) $request->query('category'))) {
            $q->where('category', $category);
        }

        if ($s = trim((string) $request->query('search'))) {
            $q->where(function ($w) use ($s) {
                $w->where('order_number', 'like', "%{$s}%")
                  ->orWhere('material', 'like', "%{$s}%")
                  ->orWhere('tooth_number', 'like', "%{$s}%")
                  ->orWhere('test_name', 'like', "%{$s}%")
                  ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', "%{$s}%"));
            });
        }

        return $q;
    }

    /**
     * @param  bool  $partial  Update only sends { status }, store sends everything.
     * @return array<string, mixed>
     */
    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'nullable' : 'required';

        return [
            'patient_id'   => $partial ? 'sometimes|integer|exists:patients,id' : 'required|integer|exists:patients,id',
            'category'     => 'nullable|in:prosthetic,diagnostic',
            'lab_type'     => "{$required}|string|max:100",
            'test_name'    => 'nullable|string|max:150',
            'doctor_id'    => 'nullable|integer|exists:doctors,id',
            'visit_id'     => 'nullable|integer|exists:visits,id',
            'tooth_number' => 'nullable|string|max:10',
            'material'     => 'nullable|string|max:100',
            'shade'        => 'nullable|string|max:20',
            'assigned_lab' => 'nullable|string|max:150',
            'due_date'     => 'nullable|date',
            'cost'         => 'nullable|integer|min:0',
            'notes'        => 'nullable|string|max:2000',
            'status'       => 'nullable|string|in:' . implode(',', LabOrder::STATUSES),
            'result_notes' => 'nullable|string|max:5000',
        ];
    }
}
