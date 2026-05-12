<?php

namespace Tests\Unit\Enums;

use App\Enums\ThemeRecurrenceRule;
use PHPUnit\Framework\TestCase;

class ThemeRecurrenceRuleTest extends TestCase
{
    public function test_has_seven_cases(): void
    {
        $this->assertCount(7, ThemeRecurrenceRule::cases());
    }

    public function test_exposes_expected_values(): void
    {
        $values = array_map(fn ($c) => $c->value, ThemeRecurrenceRule::cases());

        $this->assertSame([
            'fixed',
            'nth_weekday',
            'easter',
            'variable_annual',
            'lunar',
            'school_calendar',
            'one_time_event',
        ], $values);
    }

    public function test_does_not_include_needs_verification(): void
    {
        $this->assertNull(ThemeRecurrenceRule::tryFrom('needs_verification'));
    }
}
