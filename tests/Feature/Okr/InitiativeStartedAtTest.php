<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InitiativeStartedAtTest extends TestCase
{
    use RefreshDatabase;

    public function test_started_at_is_nullable_by_default(): void
    {
        $initiative = Initiative::factory()->create();

        $this->assertNull($initiative->started_at);
    }

    public function test_started_at_is_castable_to_carbon(): void
    {
        $initiative = Initiative::factory()->create(['started_at' => '2026-03-17']);

        $this->assertInstanceOf(Carbon::class, $initiative->started_at);
        $this->assertSame('2026-03-17', $initiative->started_at->toDateString());
    }
}
