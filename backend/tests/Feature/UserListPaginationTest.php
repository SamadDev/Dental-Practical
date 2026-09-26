<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /users used to hard-code `paginate(50)` while the roles screen read only
 * the first page, so a clinic with more than 50 accounts silently lost users
 * from that screen. The endpoint now honours `per_page` (capped) and the screen
 * walks every page.
 */
class UserListPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_user_list_honours_per_page_and_caps_it(): void
    {
        $admin = User::factory()->create();
        $admin->assignSyncRole('admin');

        User::factory()->count(5)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('per_page', 3);

        $this->app['auth']->forgetGuards();

        // The roles screen asks for 200; a client cannot lift the ceiling.
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users?per_page=9999')
            ->assertOk()
            ->assertJsonPath('per_page', 200);
    }

    public function test_the_user_list_requires_the_manage_users_permission(): void
    {
        $hygienist = User::factory()->create();
        $hygienist->assignSyncRole('hygienist');

        $this->actingAs($hygienist, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }
}
