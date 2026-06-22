<?php

namespace App\Support\Reactivation;

use App\Models\Fiche;
use App\Models\User;
use App\Services\MonthlyDigest\Composer;
use Illuminate\Support\Collection;

final readonly class ReactivationContent
{
    public function __construct(
        public int $fichesCount,
        public int $contributorsCount,
        public Collection $themes,
    ) {}

    public static function build(): self
    {
        $payload = app(Composer::class)->compose(now());

        return new self(
            fichesCount: Fiche::count(),
            contributorsCount: User::whereHas('fiches')->count(),
            themes: $payload->themes
                ->filter(fn ($occurrence): bool => ($occurrence->theme->fiches_count ?? 0) > 0)
                ->take(3)
                ->values(),
        );
    }
}
