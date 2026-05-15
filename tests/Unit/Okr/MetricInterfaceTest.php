<?php

namespace Tests\Unit\Okr;

use App\Metrics\NewsletterActivationRateMetric;
use App\Metrics\OnboardingFollowupResponseRateMetric;
use App\Metrics\OnboardingInteraction30dRateMetric;
use App\Metrics\OnboardingReturn7dRateMetric;
use App\Metrics\OnboardingSignupCountMetric;
use App\Metrics\OnboardingVerificationRateMetric;
use App\Metrics\PresentationScoreAvgMetric;
use App\Metrics\ThankRateMetric;
use App\Services\Okr\Metric;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MetricInterfaceTest extends TestCase
{
    public function test_interface_declares_compute_as_of(): void
    {
        $reflection = new ReflectionClass(Metric::class);

        $this->assertTrue($reflection->hasMethod('computeAsOf'));

        $method = $reflection->getMethod('computeAsOf');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('date', $params[0]->getName());
        $this->assertSame(CarbonImmutable::class, (string) $params[0]->getType());
    }

    public static function metricProvider(): array
    {
        return [
            [PresentationScoreAvgMetric::class],
            [OnboardingSignupCountMetric::class],
            [OnboardingVerificationRateMetric::class],
            [OnboardingReturn7dRateMetric::class],
            [OnboardingInteraction30dRateMetric::class],
            [OnboardingFollowupResponseRateMetric::class],
            [ThankRateMetric::class],
            [NewsletterActivationRateMetric::class],
        ];
    }

    #[DataProvider('metricProvider')]
    public function test_each_metric_implements_compute_as_of(string $class): void
    {
        $this->assertTrue(
            (new ReflectionClass($class))->hasMethod('computeAsOf'),
            "{$class} does not implement computeAsOf"
        );
    }
}
