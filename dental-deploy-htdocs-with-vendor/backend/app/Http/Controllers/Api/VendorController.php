<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'name');
        $dir  = $request->get('dir', 'asc');
        $query->orderBy($sort, $dir);

        $perPage = $request->get('per_page', 25);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'contact_person'      => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'email'               => 'nullable|email|max:255',
            'payment_terms_days'  => 'integer|min:0',
        ]);

        $vendor = Vendor::create($data);
        return response()->json($vendor, 201);
    }
}