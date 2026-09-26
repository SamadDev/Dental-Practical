<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login hardening: brute-force throttling, deactivation, and token lifecycle
 * (an account that is switched off must stop working immediately).
 */
class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_valid_login_returns_a_token_and_the_user(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_stops_after_six_attempts_per_minute(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong-password'])
                ->assertStatus(422);
        }

        // The seventh attempt inside the same minute is throttled, so a stolen
        // e-mail address cannot be brute-forced at speed.
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(429);
    }

    public function test_a_deactivated_account_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_deactivating_a_user_revokes_the_tokens_already_issued(): void
    {
        $admin = User::factory()->create();
        $admin->assignSyncRole('admin');

        $target = User::factory()->create();
        $target->assignSyncRole('lab');

        $token = $target->createToken('clinic-app')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/users/{$target->id}/role", ['role' => 'lab', 'is_active' => false])
            ->assertOk();

        $this->assertSame(0, $target->tokens()->count(), 'Deactivating a user must delete their API tokens.');

        // Drop the acting-as session so the old bearer token is what gets used.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_logout_only_revokes_the_current_token(): void
    {
        $user  = User::factory()->create();
        $first = $user->createToken('phone')->plainTextToken;
        $second = $user->createToken('desk')->plainTextToken;

        $this->withToken($first)->postJson('/api/v1/logout')->assertOk();

        // Requests share one booted app inside a test, and Sanctum caches the
        // resolved user on the guard — reset it before re-checking the token.
        $this->app['auth']->forgetGuards();

        $this->withToken($first)->getJson('/api/v1/me')->assertStatus(401);

        $this->app['auth']->forgetGuards();

        $this->withToken($second)->getJson('/api/v1/me')->assertOk();
    }
}
