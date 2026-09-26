<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pins the role → permission contract that the SPA gating relies on.
 *
 * The frontend hides buttons with `can('...')`; if the backend role loses that
 * permission the button disappears for everyone, and if a read-only role gains
 * it the button appears and then 403s. Both directions are asserted here.
 */
class PermissionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_every_application_role_has_a_matching_spatie_role(): void
    {
        foreach (User::ROLES as $role) {
            $this->assertTrue(
                Role::query()->where('name', $role)->where('guard_name', 'web')->exists(),
                "No Spatie role exists for User::ROLES entry '{$role}'."
            );
        }
    }

    public function test_the_money_desk_can_settle_debt_and_collect_installments(): void
    {
        foreach (['admin', 'doctor', 'receptionist'] as $role) {
            $this->assertTrue(
                $this->can($role, 'visits.pay_debt'),
                "{$role} must be able to settle short-term debt (archive pay button)."
            );
            $this->assertTrue(
                $this->can($role, 'payment_plans.pay'),
                "{$role} must be able to collect a payment-plan installment."
            );
        }
    }

    public function test_roles_that_never_handle_cash_cannot_settle_debt(): void
    {
        foreach (['hygienist', 'lab'] as $role) {
            $this->assertFalse($this->can($role, 'visits.pay_debt'), "{$role} must not settle debt.");
            $this->assertFalse($this->can($role, 'payment_plans.pay'), "{$role} must not collect installments.");
            $this->assertFalse($this->can($role, 'expenses.create'), "{$role} must not record expenses.");
        }
    }

    public function test_only_admin_can_adjust_inventory_and_receptionist_can_move_stock(): void
    {
        // inventory.adjust backs POST/PUT/DELETE /inventory — admin only.
        $this->assertTrue($this->can('admin', 'inventory.adjust'), 'admin must be able to adjust inventory.');

        foreach (['doctor', 'receptionist', 'hygienist', 'lab'] as $role) {
            $this->assertFalse(
                $this->can($role, 'inventory.adjust'),
                "{$role} must not adjust inventory — the endpoint is admin-only."
            );
        }

        $this->assertTrue($this->can('receptionist', 'inventory.move'), 'receptionist needs inventory.move.');

        foreach (['doctor', 'hygienist', 'lab'] as $role) {
            $this->assertFalse($this->can($role, 'inventory.move'), "{$role} must not move stock.");
        }
    }

    private function can(string $role, string $permission): bool
    {
        $model = Role::query()->where('name', $role)->where('guard_name', 'web')->firstOrFail();

        return $model->getAllPermissions()->pluck('name')->contains($permission);
    }
}
