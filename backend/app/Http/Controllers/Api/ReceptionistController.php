<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ReceptionistController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::role('receptionist')
            ->with(['doctorProfile', 'assignedDoctors'])
            ->orderBy('id')->get()
            ->map(fn ($u) => $this->transform($u));
        return response()->json(['data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'doctor_ids'  => 'nullable|array',
            'doctor_ids.*'=> 'integer|exists:doctors,id',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'receptionist',
            'is_active' => true,
        ]);
        $user->syncRoles(['receptionist']);

        if (!empty($data['doctor_ids'])) {
            $user->assignedDoctors()->sync($data['doctor_ids']);
        }

        return response()->json(['data' => $this->transform($user->load('assignedDoctors'))], 201);
    }

    public function show(User $user): JsonResponse
    {
        if (!$user->isReceptionist()) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['data' => $this->transform($user->load('assignedDoctors'))]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (!$user->isReceptionist()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'email'       => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password'    => 'nullable|string|min:6',
            'is_active'   => 'nullable|boolean',
            'doctor_ids'  => 'nullable|array',
            'doctor_ids.*'=> 'integer|exists:doctors,id',
        ]);

        $userUpdate = array_filter([
            'name'      => $data['name'] ?? null,
            'email'     => $data['email'] ?? null,
            'password'  => !empty($data['password']) ? Hash::make($data['password']) : null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null);

        if ($userUpdate) {
            $user->update($userUpdate);
        }

        if (array_key_exists('doctor_ids', $data)) {
            $user->assignedDoctors()->sync($data['doctor_ids'] ?? []);
        }

        return response()->json(['data' => $this->transform($user->fresh('assignedDoctors'))]);
    }

    public function destroy(User $user): JsonResponse
    {
        if (!$user->isReceptionist()) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user->delete();
        return response()->json(null, 204);
    }

    private function transform(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'is_active'  => $user->is_active,
            'doctors'    => $user->relationLoaded('assignedDoctors') ? $user->assignedDoctors->map(fn ($d) => [
                'id'       => $d->id,
                'name'     => $d->name,
                'specialty'=> $d->specialty,
                'color'    => $d->color,
            ])->all() : [],
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }
}
