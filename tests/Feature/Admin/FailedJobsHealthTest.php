<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\ServerHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FailedJobsHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_groups_failed_jobs_by_exception(): void
    {
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: boom', '2026-03-14 23:59:10');
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: boom', '2026-03-14 23:59:20');
        $this->insertFailedJob('App\\Jobs\\BetaJob', 'LogicException: nope', '2026-06-01 10:00:00');

        $summary = ServerHealth::failedJobsSummary();

        $this->assertCount(2, $summary);
        $alpha = $summary->firstWhere('label', 'RuntimeException: boom');
        $this->assertSame(2, $alpha['count']);
        $this->assertSame('2026-03-14 23:59:10', $alpha['first_seen']);
        $this->assertSame('2026-03-14 23:59:20', $alpha['last_seen']);
    }

    public function test_latest_failed_job_returns_newest_with_decoded_job_name(): void
    {
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: old', '2026-03-14 23:59:10');
        $this->insertFailedJob('App\\Jobs\\BetaJob', 'LogicException: new', '2026-06-01 10:00:00');

        $latest = ServerHealth::latestFailedJob();

        $this->assertSame('App\\Jobs\\BetaJob', $latest['job']);
        $this->assertStringContainsString('LogicException', $latest['exception']);
    }

    public function test_latest_failed_job_is_null_when_table_empty(): void
    {
        $this->assertNull(ServerHealth::latestFailedJob());
    }

    public function test_latest_failed_job_handles_malformed_payload(): void
    {
        // Insert a failed job with invalid JSON payload
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => 'not-valid-json',
            'exception' => 'RuntimeException: test error',
            'failed_at' => '2026-06-29 12:00:00',
        ]);

        $latest = ServerHealth::latestFailedJob();

        $this->assertNotNull($latest);
        $this->assertSame('Onbekende taak', $latest['job']);
        $this->assertStringContainsString('RuntimeException', $latest['exception']);
    }

    public function test_admin_can_flush_failed_jobs(): void
    {
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: boom', '2026-03-14 23:59:10');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete(route('admin.health.flush-failed'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Alle mislukte taken zijn gewist.');
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_contributor_cannot_flush_failed_jobs(): void
    {
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: boom', '2026-03-14 23:59:10');
        $user = User::factory()->create(['role' => 'contributor']);

        $this->actingAs($user)->delete(route('admin.health.flush-failed'))->assertForbidden();

        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    public function test_failed_jobs_card_renders_breakdown_and_flush_button(): void
    {
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: boom', '2026-03-14 23:59:10');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.health'));

        $response->assertOk();
        $response->assertSee('Mislukte taken');
        $response->assertSee('Wis alle mislukte taken');
        $response->assertSee('RuntimeException: boom');
    }

    public function test_failed_jobs_card_is_hidden_when_no_failures(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.health'));

        $response->assertOk();
        $response->assertDontSee('Wis alle mislukte taken');
    }

    public function test_failed_jobs_same_day_burst_shows_single_date(): void
    {
        // Two failures on the same calendar day but different times
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: burst', '2026-03-14 23:59:10');
        $this->insertFailedJob('App\\Jobs\\AlphaJob', 'RuntimeException: burst', '2026-03-14 23:59:50');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.health'));

        $response->assertOk();
        // Should NOT show redundant range "14 mrt 2026 – 14 mrt 2026"
        $response->assertDontSee('14 mrt 2026 –');
        // But the exception text should be visible
        $response->assertSee('RuntimeException: burst');
    }

    private function insertFailedJob(string $displayName, string $exception, string $failedAt): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => $displayName, 'job' => $displayName]),
            'exception' => $exception,
            'failed_at' => $failedAt,
        ]);
    }
}
