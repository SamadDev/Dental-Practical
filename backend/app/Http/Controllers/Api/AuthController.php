<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
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
            'user'  => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()->load(['doctorProfile', 'assignedDoctors'])));
    }

    /** Admin: list all users with their profiles. */
    /**
     * GET /users - admin user list.
     *
     * `per_page` is honoured but capped, so the roles screen can load the whole
     * clinic in one pass without letting a client request the entire table.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 50), 1), 200);

        $users = User::query()
            ->with(['doctorProfile', 'assignedDoctors'])
            ->select('id', 'name', 'email', 'role', 'is_active', 'created_at')
            ->orderBy('role')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($users);
    }

    /** Admin: create user (admin/receptionist/hygienist/lab; doctors via DoctorController). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
            'role'      => ['required', Rule::in(User::ROLES)],
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ]);
        $user->assignSyncRole($data['role']);

        return response()->json($this->userPayload($user), 201);
    }

    /** Admin: update user. */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            'password'  => 'sometimes|string|min:6',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        // Deactivating an account must also revoke its issued API tokens,
        // otherwise an already signed-in device keeps full access until it
        // happens to log out (login-time checks never run again).
        if (array_key_exists('is_active', $data) && ! $data['is_active']) {
            $user->tokens()->delete();
        }

        return response()->json($this->userPayload($user->refresh()->load(['doctorProfile', 'assignedDoctors'])));
    }

    /** Admin: delete user. */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 422);
        }
        $user->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * PUT /user/profile — every account edits its own name/email.
     * Returns the same payload as /me so the SPA can refresh in place.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return response()->json($this->userPayload($user));
    }

    /**
     * PUT /user/password — requires the current password; the field names
     * match Laravel's `confirmed` rule so 422 errors map to the form fields.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        // `password` is cast to `hashed` — assign the plain value.
        $user->update(['password' => $data['password']]);

        return response()->json(['ok' => true]);
    }

    /** GET /roles — every role with its permission keys, for the Roles page. */
    public function roles(): JsonResponse
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                // No schema columns for these — the UI falls back to its own
                // translated description; is_active is informational only.
                'description' => null,
                'is_active'   => true,
                'users_count' => (int) $role->users_count,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ]);

        return response()->json($roles);
    }

    /**
     * PUT /users/{user}/role — change a user's role / active flag.
     * Guards the admin against accidentally demoting or deactivating
     * their own account (which would lock them out of this page).
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role'      => ['required', Rule::in(User::ROLES)],
            'is_active' => 'sometimes|boolean',
        ]);

        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : (bool) $user->is_active;

        if ($user->is($request->user()) && ($data['role'] !== 'admin' || ! $isActive)) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role or active status.',
            ]);
        }

        $user->assignSyncRole($data['role']);

        if (array_key_exists('is_active', $data)) {
            $user->forceFill(['is_active' => $isActive])->save();

            // Same reason as in update(): switching an account off must kill
            // any token that is already in circulation.
            if (! $isActive) {
                $user->tokens()->delete();
            }
        }

        return response()->json($this->userPayload($user->load(['doctorProfile', 'assignedDoctors'])));
    }

    private function userPayload(User $user): array

    {
        $payload = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->roles->first()?->name ?? $user->role,
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];

        // Doctor / hygienist: link to their profile
        if ($user->isDoctor() || $user->isHygienist()) {
            $payload['doctor_profile'] = $user->doctorProfile ? [
                'id'       => $user->doctorProfile->id,
                'name'     => $user->doctorProfile->name,
                'specialty'=> $user->doctorProfile->specialty,
                'color'    => $user->doctorProfile->color,
            ] : null;
        }

        // Receptionist: list of assigned doctors
        if ($user->isReceptionist()) {
            $payload['assigned_doctors'] = $user->assignedDoctors->map(fn ($d) => [
                'id'       => $d->id,
                'name'     => $d->name,
                'specialty'=> $d->specialty,
                'color'    => $d->color,
            ])->all();
        }

        return $payload;
    }
}
