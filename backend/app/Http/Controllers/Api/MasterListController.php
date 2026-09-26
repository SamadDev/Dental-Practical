<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Clinic-wide suggestion lists behind the patient form's autocomplete
 * fields (allergies, diseases, visit reasons). GET returns [{id, name}];
 * POST is idempotent — re-adding an existing name returns it, so the
 * form never fails on a duplicate suggestion.
 */
class MasterListController extends Controller
{
    public function allergies(): JsonResponse
    {
        return $this->index('allergy');
    }

    public function diseases(): JsonResponse
    {
        return $this->index('disease');
    }

    public function visitReasons(): JsonResponse
    {
        return $this->index('visit_reason');
    }

    public function storeAllergy(Request $request): JsonResponse
    {
        return $this->store($request, 'allergy');
    }

    public function storeDisease(Request $request): JsonResponse
    {
        return $this->store($request, 'disease');
    }

    public function storeVisitReason(Request $request): JsonResponse
    {
        return $this->store($request, 'visit_reason');
    }

    private function index(string $type): JsonResponse
    {
        return response()->json(
            MasterList::where('type', $type)->orderBy('name')->get(['id', 'name']),
        );
    }

    private function store(Request $request, string $type): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
        ]);

        $row = MasterList::firstOrCreate([
            'type' => $type,
            'name' => trim($data['name']),
        ]);

        return response()->json($row->only(['id', 'name']), 201);
    }
}
