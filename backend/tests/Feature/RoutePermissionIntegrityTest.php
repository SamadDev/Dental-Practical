<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Guards the recurring "controller exists but the route/permission was never
 * registered" bug class.
 *
 * A permission name that is missing from the catalogue makes its route
 * unreachable for *every* role (silent 403), and a permission no role holds is
 * dead weight that hides intent. Neither shows up in a normal request test, so
 * both are pinned here against the live route table and the seeder output.
 */
class RoutePermissionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_every_permission_referenced_by_a_route_exists_in_the_catalogue(): void
    {
        $declared = Permission::pluck('name')->all();
        $missing  = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                    continue;
                }

                foreach (explode(',', substr($middleware, strlen('permission:'))) as $name) {
                    $name = trim($name);

                    if ($name !== '' && ! in_array($name, $declared, true)) {
                        $missing[] = $route->methods()[0] . ' /' . $route->uri() . ' → ' . $name;
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Routes below require a permission that is not in the catalogue, so they 403 for every role:\n - "
                . implode("\n - ", $missing)
        );
    }

    public function test_every_declared_permission_is_granted_to_at_least_one_role(): void
    {
        $granted = Role::with('permissions')->get()
            ->flatMap->permissions
            ->pluck('name')
            ->unique();

        $dead = Permission::pluck('name')->diff($granted)->values()->all();

        $this->assertSame(
            [],
            $dead,
            'Permissions that no role holds (unreachable feature or a forgotten seeder entry): '
                . implode(', ', $dead)
        );
    }

    public function test_no_route_is_registered_twice(): void
    {
        $seen = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $key          = $method . ' /' . $route->uri();
                $seen[$key]   = ($seen[$key] ?? 0) + 1;
            }
        }

        $duplicates = array_keys(array_filter($seen, fn (int $count) => $count > 1));

        $this->assertSame([], $duplicates, 'Duplicate route registrations: ' . implode(', ', $duplicates));
    }

    public function test_every_token_protected_api_route_is_permission_gated(): void
    {
        // Self-service endpoints are intentionally open to any signed-in user.
        $exempt = [
            'api/v1/logout',
            'api/v1/me',
            'api/v1/user/profile',
            'api/v1/user/password',
        ];

        $ungated = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/') || in_array($route->uri(), $exempt, true)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            if (! in_array('auth:sanctum', $middleware, true)) {
                continue;
            }

            $hasPermissionCheck = collect($middleware)->contains(
                fn ($m) => is_string($m) && str_starts_with($m, 'permission:')
            );

            if (! $hasPermissionCheck) {
                $ungated[] = $route->methods()[0] . ' /' . $route->uri();
            }
        }

        $this->assertSame(
            [],
            $ungated,
            "Token-protected routes with no permission check (any logged-in user can reach them):\n - "
                . implode("\n - ", $ungated)
        );
    }
}
