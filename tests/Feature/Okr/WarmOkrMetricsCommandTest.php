<?php

namespace Tests\Feature\Okr;

use Database\Seeders\OkrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WarmOkrMetricsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_command_warms_metric_caches_and_succeeds(): void
    {
        $this->seed(OkrSeeder::class);

        $this->assertFalse(Cache::has('okr.compute.onboarding_signup_count.month'));

        $this->artisan('okr:warm-metrics')->assertSuccessful();

        $this->assertTrue(Cache::has('okr.compute.onboarding_signup_count.month'));
        $this->assertTrue(Cache::has('okr.compute.thank_rate.alltime'));
    }

    public function test_command_is_idempotent(): void
    {
        $this->seed(OkrSeeder::class);

        $this->artisan('okr:warm-metrics')->assertSuccessful();
        $first = Cache::get('okr.compute.onboarding_signup_count.month');

        $this->artisan('okr:warm-metrics')->assertSuccessful();

        $this->assertEquals($first, Cache::get('okr.compute.onboarding_signup_count.month'));
    }
}
