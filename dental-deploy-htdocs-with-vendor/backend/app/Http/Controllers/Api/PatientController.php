<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\HandlesDataTableQueries;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use HandlesDataTableQueries;

    /** sort key => column. Aggregates are selected as aliases below. */
    private const SORTABLE = [
        'name'             => 'patients.name',
        'phone'            => 'patients.phone',
        'age'              => 'patients.age',
        'appointment_date' => 'patients.appointment_date',
        'created_at'       => 'patients.created_at',
        'outstanding_debt' => 'outstanding_debt',
        'visits_count'     => 'visits_count',
        'last_visit_at'    => 'last_visit_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $q = Patient::query()
            ->select('patients.*')
            // Aggregates as subquery selects rather than a GROUP BY join, so
            // they can be both sorted on and filtered by without duplicating rows.
            ->withCount('visits')
            ->withSum('visits as outstanding_debt', 'short_term_debt')
            ->addSelect([
                'last_visit_at' => Visit::selectRaw('MAX(created_at)')
                    ->whereColumn('visits.patient_id', 'patients.id'),
            ]);

        $this->applyPatientFilters($q, $request);
        $this->applySort($q, $request, self::SORTABLE, 'created_at');

        $page = $q->paginate($this->perPage($request));

        // withSum yields null for a patient with no visits; the table's money
        // column and its "has debt" styling both expect a plain integer.
        $page->getCollection()->transform(function (Patient $p) {
            $p->outstanding_debt = (int) $p->outstanding_debt;

            return $p;
        });

        return response()->json($page);
    }

    /**
     * Counts for the filter bar's quick-filter chips, over the *unfiltered*
     * table — the badges must not change as the user narrows the list.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total'      => Patient::count(),
            'with_debt'  => Patient::whereHas('visits', fn ($v) => $v->where('short_term_debt', '>', 0))->count(),
            'smokers'    => Patient::where('is_smoker', true)->count(),
            'upcoming'   => Patient::whereNotNull('appointment_date')
                ->where('appointment_date', '>=', now()->startOfDay()->toDateTimeString())
                ->count(),
        ]);
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
        return response()->json(Patient::create($this->validatePatient($request, true)), 201);
    }

    public function update(Request $request, Patient $patient): JsonResponse
    {
        $patient->update($this->validatePatient($request, false));

        return response()->json($patient);
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $patient->delete();

        return response()->json(['ok' => true]);
    }

    private function validatePatient(Request $request, bool $creating): array
    {
        return $request->validate([
            'name'             => ($creating ? 'required' : 'sometimes').'|string|max:255',
            'phone'            => 'nullable|string|max:50',
            'age'              => 'nullable|integer|min:0|max:150',
            'appointment_date' => 'nullable|date',
            'is_smoker'        => 'nullable|boolean',
            'medical_notes'    => 'nullable|string',
        ]);
    }

    /** Advanced filter set driven by the DataTable's filter bar. */
    private function applyPatientFilters($q, Request $request): void
    {
        if ($s = trim((string) $request->query('search'))) {
            $q->where(function ($w) use ($s) {
                $w->where('patients.name', 'like', "%{$s}%")
                    ->orWhere('patients.phone', 'like', "%{$s}%")
                    ->orWhere('patients.medical_notes', 'like', "%{$s}%");
            });
        }

        if ($request->boolean('has_debt')) {
            $q->whereHas('visits', fn ($v) => $v->where('short_term_debt', '>', 0));
        }

        // Tri-state: absent = no filter, '1'/'0' = filter on either value.
        if ($request->filled('is_smoker')) {
            $q->where('patients.is_smoker', $request->boolean('is_smoker'));
        }

        $this->applyAmountRange($q, $request, 'patients.age', 'age_min', 'age_max');
        $this->applyDateRange($q, $request, 'patients.created_at', 'created_from', 'created_to');

        // appointment_date is a text column (see migration), but 'YYYY-MM-DDTHH:mm'
        // sorts and compares correctly as a string, so range filters still work.
        match ((string) $request->query('appointment')) {
            'upcoming' => $q->whereNotNull('patients.appointment_date')
                ->where('patients.appointment_date', '>=', now()->startOfDay()->format('Y-m-d')),
            'past' => $q->whereNotNull('patients.appointment_date')
                ->where('patients.appointment_date', '<', now()->startOfDay()->format('Y-m-d')),
            'none' => $q->whereNull('patients.appointment_date'),
            default => null,
        };
    }
}
