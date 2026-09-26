<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Static guard for the SPA half of the permission contract.
 *
 * Backend routes are protected by Spatie middleware, but the buttons that call
 * them are only hidden when the view wraps them in `can('...')`. A missing gate
 * shows a control to a role that will always get a 403 (the exact bug class
 * found on the archive "collect debt" button), so the gates are pinned here.
 */
class UiPermissionGatingTest extends TestCase
{
    /** @var array<string, list<string>> view → permissions that must gate it */
    private const EXPECTATIONS = [
        'views/ArchiveView.vue'      => ['queue.manage', 'visits.pay_debt'],
        'views/PaymentPlansView.vue' => ['payment_plans.pay', 'payment_plans.edit'],
        'views/InventoryView.vue'    => ['inventory.adjust', 'inventory.move'],
    ];

    public function test_admin_only_actions_are_hidden_behind_can_checks(): void
    {
        foreach (self::EXPECTATIONS as $view => $permissions) {
            $source = $this->source($view);

            foreach ($permissions as $permission) {
                $this->assertStringContainsString(
                    "can('{$permission}')",
                    $source,
                    "{$view} must hide its '{$permission}' action with can('{$permission}')"
                );
            }
        }
    }

    private function source(string $view): string
    {
        $path = dirname(__DIR__, 3) . '/frontend/src/' . $view;

        $this->assertFileExists($path, "View not found: {$path}");

        return (string) file_get_contents($path);
    }
}
