<?php

namespace Tests\Unit\Okr;

use App\Services\Okr\KrHealth;
use PHPUnit\Framework\TestCase;

class KrHealthTest extends TestCase
{
    public function test_null_current_is_neutral(): void
    {
        $this->assertSame('neutral', KrHealth::status(null, 50, 'thank_rate'));
        $this->assertSame('text-[var(--color-primary)]', KrHealth::colorClass(null, 50, 'thank_rate'));
    }

    public function test_presentation_score_uses_absolute_scale_regardless_of_target(): void
    {
        // 0–100 quality scale: green ≥70 / amber 40–69 / red <40
        $this->assertSame('good', KrHealth::status(75, null, 'presentation_score_avg'));
        $this->assertSame('mid', KrHealth::status(50, null, 'presentation_score_avg'));
        $this->assertSame('bad', KrHealth::status(36, 50, 'presentation_score_avg'));
    }

    public function test_rate_metric_is_goal_relative_and_strict(): void
    {
        // green only at/above target; amber ≥40% of target; red below that.
        $this->assertSame('good', KrHealth::status(50, 50, 'thank_rate'));    // 100% — goal met
        $this->assertSame('good', KrHealth::status(60, 50, 'thank_rate'));    // above goal
        $this->assertSame('mid', KrHealth::status(49, 50, 'thank_rate'));     // 98% — still on the way
        $this->assertSame('mid', KrHealth::status(35, 50, 'thank_rate'));     // 70% — NOT green anymore
        $this->assertSame('mid', KrHealth::status(20, 50, 'thank_rate'));     // 40% — boundary
        $this->assertSame('bad', KrHealth::status(10, 50, 'thank_rate'));     // 20%
    }

    public function test_no_target_is_neutral_for_non_absolute_metrics(): void
    {
        $this->assertSame('neutral', KrHealth::status(4849, null, 'onboarding_signup_count'));
        $this->assertSame('neutral', KrHealth::status(35, 0, 'thank_rate'));
    }

    public function test_color_class_mapping(): void
    {
        $this->assertSame('text-green-700', KrHealth::colorClass(50, 50, 'thank_rate'));
        $this->assertSame('text-amber-600', KrHealth::colorClass(20, 50, 'thank_rate'));
        $this->assertSame('text-red-600', KrHealth::colorClass(10, 50, 'thank_rate'));
        $this->assertSame('text-[var(--color-primary)]', KrHealth::colorClass(4849, null, 'onboarding_signup_count'));
    }

    public function test_bar_class_mapping(): void
    {
        $this->assertSame('bg-green-600', KrHealth::barClass(50, 50, 'thank_rate'));
        $this->assertSame('bg-amber-500', KrHealth::barClass(20, 50, 'thank_rate'));
        $this->assertSame('bg-red-500', KrHealth::barClass(10, 50, 'thank_rate'));
        $this->assertSame('bg-[var(--color-primary)]', KrHealth::barClass(4849, null, 'onboarding_signup_count'));
    }
}
