<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    public function index(): JsonResponse
    {
        $doctors = Doctor::with('user')->orderBy('id')->get()
            ->map(fn ($d) => $this->transform($d));
        return response()->json(['data' => $doctors]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
            'specialty' => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:20',
            'color'     => 'nullable|string|max:7',
            'bio'       => 'nullable|string',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'doctor',
            'is_active' => true,
        ]);
        $user->syncRoles(['doctor']);

        $doctor = Doctor::create([
            'user_id'   => $user->id,
            'specialty' => $data['specialty'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'color'     => $data['color'] ?? '#6366f1',
            'bio'       => $data['bio'] ?? null,
        ]);

        return response()->json(['data' => $this->transform($doctor->load('user'))], 201);
    }

    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json(['data' => $this->transform($doctor->load(['user', 'receptionists']))]);
    }

    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => ['sometimes', 'email', Rule::unique('users')->ignore($doctor->user_id)],
            'specialty' => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:20',
            'color'     => 'nullable|string|max:7',
            'bio'       => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $doctor->user->update(array_filter([
            'name'      => $data['name'] ?? null,
            'email'     => $data['email'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        $doctor->update(array_filter([
            'specialty' => $data['specialty'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'color'     => $data['color'] ?? null,
            'bio'       => $data['bio'] ?? null,
        ], fn ($v) => $v !== null));

        return response()->json(['data' => $this->transform($doctor->fresh('user'))]);
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        $doctor->user->delete();
        $doctor->delete();
        return response()->json(null, 204);
    }

    private function transform(Doctor $doctor): array
    {
        return [
            'id'            => $doctor->id,
            'name'          => $doctor->name,
            'email'         => $doctor->email,
            'specialty'     => $doctor->specialty,
            'phone'         => $doctor->phone,
            'color'         => $doctor->color,
            'bio'           => $doctor->bio,
            'is_active'     => $doctor->is_active,
            'receptionists' => $doctor->relationLoaded('receptionists') ? $doctor->receptionists->map(fn ($u) => [
                'id'   => $u->id,
                'name' => $u->name,
            ])->all() : [],
            'created_at'    => $doctor->created_at?->toISOString(),
            'updated_at'    => $doctor->updated_at?->toISOString(),
        ];
    }
}
