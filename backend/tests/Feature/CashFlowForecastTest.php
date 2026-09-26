<?php

namespace Tests\Feature;

use App\Models\AqsatContract;
use App\Models\CashFlowForecast;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cash-flow shipped as a complete backend (controller + model + migration +
 * permissions) with no screen and no tests at all — the bug class this suite
 * exists to catch. These pin the contract the new CashFlowView relies on.
 */
class CashFlowForecastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignSyncRole($role);

        return $user;
    }

    private function manualEntry(array $overrides = []): CashFlowForecast
    {
        return CashFlowForecast::create(array_merge([
            'forecast_date' => now()->addDays(3)->toDateString(),
            'type'          => 'inflow',
            'source'        => 'manual',
            'description'   => 'Insurance payout',
            'amount'        => 250000,
            'status'        => 'confirmed',
        ], $overrides));
    }

    public function test_forecast_returns_daily_buckets_totals_and_weekly_rollup(): void
    {
        $this->manualEntry();

        $from = now()->toDateString();
        $to   = now()->addDays(7)->toDateString();

        $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/cash-flow/forecast?from={$from}&to={$to}")
            ->assertOk()
            ->assertJsonPath('currency', 'IQD')
            ->assertJsonStructure([
                'range' => ['from', 'to'],
                'daily' => [['date', 'inflow', 'outflow', 'net', 'items']],
                'totals' => ['total_inflow', 'total_outflow', 'net', 'running_balance'],
            ])
            // Nothing else is seeded in the test DB, so the manual entry is the
            // entire projection.
            ->assertJsonPath('totals.total_inflow', 250000)
            ->assertJsonPath('totals.total_outflow', 0)
            ->assertJsonPath('totals.net', 250000);

        $this->app['auth']->forgetGuards();

        $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/cash-flow/weekly?from={$from}&to={$to}")
            ->assertOk()
            ->assertJsonStructure(['weekly' => [['week_start', 'inflow', 'outflow', 'net']]]);
    }

    public function test_manual_entries_can_be_excluded_from_the_projection(): void
    {
        $this->manualEntry();

        $from = now()->toDateString();
        $to   = now()->addDays(7)->toDateString();

        $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/cash-flow/forecast?from={$from}&to={$to}&include_manual=0")
            ->assertOk()
            ->assertJsonPath('totals.total_inflow', 0);
    }

    public function test_manual_entries_can_be_created_updated_and_deleted(): void
    {
        $this->actingAs($this->userWithRole('admin'), 'sanctum');

        $created = $this->postJson('/api/v1/cash-flow/manual', [
            'forecast_date' => now()->addDays(10)->toDateString(),
            'type'          => 'outflow',
            'source'        => 'manual',
            'description'   => 'Lab equipment payment',
            'amount'        => 400000,
        ])
            ->assertCreated()
            ->assertJsonPath('status', 'projected') // default when omitted
            ->json();

        $this->putJson("/api/v1/cash-flow/manual/{$created['id']}", [
            'amount' => 450000,
            'status' => 'confirmed',
        ])
            ->assertOk()
            ->assertJsonPath('amount', 450000)
            ->assertJsonPath('status', 'confirmed');

        $this->deleteJson("/api/v1/cash-flow/manual/{$created['id']}")->assertOk();

        $this->assertDatabaseCount('cash_flow_forecasts', 0);
    }

    public function test_the_manual_list_honours_paging_sort_search_and_totals(): void
    {
        foreach (range(1, 6) as $i) {
            $this->manualEntry([
                'forecast_date' => now()->addDays($i)->toDateString(),
                'type'          => $i === 6 ? 'outflow' : 'inflow',
                'description'   => $i === 6 ? 'Rent' : "Supplies {$i}",
                'amount'        => $i * 10000,
            ]);
        }

        $from = now()->toDateString();
        $to   = now()->addDays(30)->toDateString();

        // per_page is clamped by the shared trait (floor 5, ceiling 200).
        $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/cash-flow/manual?per_page=5&from={$from}&to={$to}")
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('totals.inflow', 150000)   // 10k + 20k + 30k + 40k + 50k
            ->assertJsonPath('totals.outflow', 60000)
            ->assertJsonPath('totals.net', 90000);

        $this->app['auth']->forgetGuards();

        $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/cash-flow/manual?sort=amount&dir=asc&search=Rent&from={$from}&to={$to}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Rent')
            ->assertJsonPath('data.0.amount', 60000);
    }

    public function test_manual_entry_validation_is_enforced(): void
    {
        $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->postJson('/api/v1/cash-flow/manual', [
                'forecast_date' => 'not-a-date',
                'type'          => 'sideways',
                'description'   => '',
                'amount'        => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['forecast_date', 'type', 'description', 'amount']);
    }

    public function test_generate_from_aqsat_creates_projected_rows_and_replaces_them(): void
    {
        $patient = Patient::create(['name' => 'Aram Mohammed', 'phone' => '+964 750 000 0001']);

        AqsatContract::create([
            'patient_id'        => $patient->id,
            'treatment_name'    => 'Orthodontics',
            'total_amount'      => 300000,
            'remaining_balance' => 300000,
            'status'            => 'active',
        ]);

        $this->actingAs($this->userWithRole('admin'), 'sanctum');

        $this->postJson('/api/v1/cash-flow/generate-aqsat?months=3')->assertOk();
        $this->assertDatabaseCount('cash_flow_forecasts', 3);

        // Re-running replaces the auto-generated rows instead of duplicating them.
        $this->postJson('/api/v1/cash-flow/generate-aqsat?months=3')->assertOk();
        $this->assertDatabaseCount('cash_flow_forecasts', 3);
        $this->assertDatabaseHas('cash_flow_forecasts', ['source' => 'aqsat', 'type' => 'inflow']);
    }

    public function test_cash_flow_is_closed_to_roles_without_the_finance_permission(): void
    {
        $this->actingAs($this->userWithRole('receptionist'), 'sanctum');

        $this->getJson('/api/v1/cash-flow/forecast')->assertForbidden();
        $this->getJson('/api/v1/cash-flow/manual')->assertForbidden();

        $this->postJson('/api/v1/cash-flow/manual', [
            'forecast_date' => now()->addDays(1)->toDateString(),
            'type'          => 'inflow',
            'source'        => 'manual',
            'description'   => 'Not allowed',
            'amount'        => 1000,
        ])->assertForbidden();

        $this->postJson('/api/v1/cash-flow/generate-aqsat')->assertForbidden();
    }
}
