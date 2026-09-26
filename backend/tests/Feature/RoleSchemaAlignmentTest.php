<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `users.role` is constrained by the database (enum/CHECK) while the allowed
 * values are declared in App\Models\User::ROLES. When the two drift, the admin
 * UI offers a role the database rejects and the request dies with an integrity
 * violation (a 500) — exactly what happened to the `lab` role, which existed in
 * code, in the permissions seeder and in the SPA but not in the column.
 *
 * This test persists every declared role so the drift can never come back.
 */
class RoleSchemaAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_role_column_accepts_every_declared_role(): void
    {
        foreach (User::ROLES as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertSame(
                $role,
                $user->fresh()->role,
                "users.role rejected '{$role}' — the column enum and User::ROLES have drifted apart."
            );
        }
    }

    public function test_an_admin_can_switch_a_user_to_every_declared_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignSyncRole('admin');

        foreach (User::ROLES as $role) {
            $target = User::factory()->create(['role' => 'receptionist']);
            $target->assignSyncRole('receptionist');

            $this->actingAs($admin, 'sanctum')
                ->putJson("/api/v1/users/{$target->id}/role", ['role' => $role, 'is_active' => true])
                ->assertOk();

            $this->assertSame($role, $target->fresh()->role);

            // Requests share one booted app inside a test, so reset the guard
            // rather than letting it remember the previous request.
            $this->app['auth']->forgetGuards();
        }
    }
}
