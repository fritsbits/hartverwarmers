<?php

namespace Tests\Unit\Support;

use App\Support\PostedMoment;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PostedMomentTest extends TestCase
{
    /** 2 september 2026, 14:00 in Brussel. */
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::create(2026, 9, 2, 14, 0, 0, 'Europe/Brussels');
    }

    public function test_within_the_hour_reads_as_just_posted(): void
    {
        $moment = PostedMoment::for($this->now()->subMinutes(20), $this->now());

        $this->assertSame('net gepost', $moment->label);
        $this->assertTrue($moment->isFresh);
    }

    public function test_earlier_today_reads_as_vandaag(): void
    {
        $moment = PostedMoment::for($this->now()->subHours(5), $this->now());

        $this->assertSame('vandaag', $moment->label);
        $this->assertTrue($moment->isFresh);
    }

    public function test_yesterday_reads_as_gisteren(): void
    {
        $moment = PostedMoment::for($this->now()->subDay(), $this->now());

        $this->assertSame('gisteren', $moment->label);
        $this->assertTrue($moment->isFresh);
    }

    public function test_a_few_days_ago_counts_the_days(): void
    {
        $moment = PostedMoment::for($this->now()->subDays(4), $this->now());

        $this->assertSame('4 dagen geleden', $moment->label);
        $this->assertTrue($moment->isFresh);
    }

    public function test_one_to_two_weeks_ago_reads_as_vorige_week(): void
    {
        $this->assertSame('vorige week', PostedMoment::for($this->now()->subDays(7), $this->now())->label);
        $this->assertSame('vorige week', PostedMoment::for($this->now()->subDays(13), $this->now())->label);
    }

    public function test_older_than_two_weeks_falls_back_to_the_month_name(): void
    {
        $moment = PostedMoment::for($this->now()->subDays(14), $this->now());

        $this->assertSame('augustus', $moment->label);
        $this->assertFalse($moment->isFresh);
    }

    public function test_a_month_from_an_earlier_year_carries_the_year(): void
    {
        $moment = PostedMoment::for(
            CarbonImmutable::create(2025, 11, 20, 10, 0, 0, 'Europe/Brussels'),
            $this->now(),
        );

        $this->assertSame('november 2025', $moment->label);
        $this->assertFalse($moment->isFresh);
    }

    public function test_a_utc_timestamp_is_read_in_brussels_time(): void
    {
        // 1 september 23:30 UTC is al 2 september, 01:30 in Brussel: vandaag dus.
        $moment = PostedMoment::for(
            CarbonImmutable::create(2026, 9, 1, 23, 30, 0, 'UTC'),
            $this->now(),
        );

        $this->assertSame('vandaag', $moment->label);
    }

    public function test_a_moment_in_the_future_never_reads_as_negative(): void
    {
        $moment = PostedMoment::for($this->now()->addHours(3), $this->now());

        $this->assertSame('vandaag', $moment->label);
        $this->assertTrue($moment->isFresh);
    }
}
