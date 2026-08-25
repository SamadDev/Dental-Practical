<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'device_name' => 'sometimes|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is deactivated.'],
            ]);
        }

        $token = $user->createToken($request->device_name ?? 'clinic-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'permissions' => $this->getPermissions($user->role),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'permissions' => $this->getPermissions($user->role),
        ]);
    }

    /** Admin: list all users. */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json(
            User::query()
                ->select('id', 'name', 'email', 'role', 'is_active', 'created_at')
                ->orderBy('role')
                ->orderBy('name')
                ->paginate(50)
        );
    }

    /** Admin: create user. */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,doctor,receptionist,hygienist',
            'is_active'=> 'sometimes|boolean',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'is_active'=> $data['is_active'] ?? true,
        ]);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ], 201);
    }

    /** Admin: update user. */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
            'password'   => 'sometimes|string|min:6',
            'role'       => 'sometimes|in:admin,doctor,receptionist,hygienist',
            'is_active'  => 'sometimes|boolean',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
            'is_active' => $user->is_active,
        ]);
    }

    /** Admin: delete user. */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete yourself'], 422);
        }
        $user->delete();
        return response()->json(['ok' => true]);
    }

    private function getPermissions(string $role): array
    {
        $user = new User();
        $user->role = $role;
        $allPerms = [
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'queue.view', 'queue.manage',
            'visits.view', 'visits.create', 'visits.edit', 'visits.checkout', 'visits.xray', 'visits.pay_debt',
            'archive.view',
            'aqsat.view', 'aqsat.create', 'aqsat.edit',
            'payment_plans.view', 'payment_plans.create', 'payment_plans.edit', 'payment_plans.pay',
            'expenses.view', 'expenses.create', 'expenses.delete',
            'inventory.view', 'inventory.move', 'inventory.adjust',
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.po',
            'cash_flow.view', 'cash_flow.manage',
            'dashboard.view',
            'users.manage',
        ];
        return array_filter($allPerms, fn ($p) => $user->hasPermission($p));
    }

    private function authorizeAdmin(Request $request): void
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Admin only');
        }
    }
}