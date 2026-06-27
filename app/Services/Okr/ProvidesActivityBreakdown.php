<?php

namespace App\Services\Okr;

use Carbon\CarbonImmutable;

/**
 * A ratio metric that can break its percentage down into the concrete daily
 * counts it is computed from: the "effort" (the denominator, e.g. mails sent)
 * and the "result" (the numerator, e.g. recipients who came back). This lets
 * the initiative chart show real quantities instead of an abstract percentage.
 */
interface ProvidesActivityBreakdown
{
    /**
     * One row per day in [$from, $to] (zero-filled), oldest first.
     *
     * @return array<int, array{label: string, effort: int, result: int}>
     */
    public function activityByDay(CarbonImmutable $from, CarbonImmutable $to): array;

    /**
     * Plain-language name for the denominator, e.g. "verstuurde mails".
     */
    public function effortLabel(): string;

    /**
     * Plain-language name for the numerator, e.g. "opnieuw actief".
     */
    public function resultLabel(): string;
}
