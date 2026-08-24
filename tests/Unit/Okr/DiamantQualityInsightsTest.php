<?php

namespace Tests\Unit\Okr;

use App\Models\Fiche;
use App\Services\Okr\DiamantQualityInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DiamantQualityInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cohorts_average_the_fiches_created_in_each_month(): void
    {
        $this->createScored([80, 60], createdAt: now()->subMonths(2));
        $this->createScored([40, 20, 30], createdAt: now()->subMonth());

        $cohorts = collect($this->insights()->cohorts('quarter'));

        $twoMonthsAgo = $cohorts->firstWhere('label', now()->subMonths(2)->isoFormat('MMM YY'));
        $this->assertSame(2, $twoMonthsAgo['fiches']);
        $this->assertSame(70, $twoMonthsAgo['avg']);

        $lastMonth = $cohorts->firstWhere('label', now()->subMonth()->isoFormat('MMM YY'));
        $this->assertSame(3, $lastMonth['fiches']);
        $this->assertSame(30, $lastMonth['avg']);
    }

    public function test_cohorts_report_empty_months_without_inventing_a_value(): void
    {
        $this->createScored([80], createdAt: now());

        $cohorts = collect($this->insights()->cohorts('quarter'));

        $emptyMonth = $cohorts->firstWhere('label', now()->subMonths(3)->isoFormat('MMM YY'));
        $this->assertSame(0, $emptyMonth['fiches']);
        $this->assertNull($emptyMonth['avg']);
        $this->assertFalse($emptyMonth['thin']);
    }

    public function test_cohorts_flag_a_month_that_is_too_thin_to_read(): void
    {
        $this->createScored([90, 10], createdAt: now());

        $thisMonth = collect($this->insights()->cohorts('month'))
            ->firstWhere('label', now()->isoFormat('MMM YY'));

        $this->assertTrue($thisMonth['thin']);
        $this->assertSame(50, $thisMonth['avg']);
    }

    public function test_cohorts_bucket_on_creation_not_on_assessment(): void
    {
        // The assessment job writes with updateQuietly(), which bumps updated_at —
        // so only created_at says when a person actually made the fiche.
        Fiche::factory()->published()->withQualityScore(90)->create([
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
            'quality_assessed_at' => now(),
        ]);

        $cohorts = collect($this->insights()->cohorts('quarter'));

        $this->assertSame(1, $cohorts->firstWhere('label', now()->subMonths(2)->isoFormat('MMM YY'))['fiches']);
        $this->assertSame(0, $cohorts->firstWhere('label', now()->isoFormat('MMM YY'))['fiches']);
    }

    public function test_range_decides_how_many_months_are_returned(): void
    {
        $insights = $this->insights();

        $this->assertCount(3, $insights->cohorts('week'));
        $this->assertCount(6, $insights->cohorts('month'));
        $this->assertCount(12, $insights->cohorts('quarter'));
        $this->assertCount(36, $insights->cohorts('alltime'));
    }

    public function test_distribution_counts_every_score_in_a_band_of_ten(): void
    {
        $this->createScored([0, 5, 62, 68, 72, 100]);

        $bands = collect($this->insights()->distribution()['bands']);

        $this->assertSame(2, $bands->firstWhere('label', '0–9')['count']);
        $this->assertSame(2, $bands->firstWhere('label', '60–69')['count']);
        $this->assertSame(1, $bands->firstWhere('label', '70–79')['count']);
        $this->assertSame(1, $bands->firstWhere('label', '90–100')['count']);
        $this->assertSame(10, $bands->count());
    }

    public function test_distribution_marks_which_bands_clear_the_threshold(): void
    {
        $strong = collect($this->insights()->distribution()['bands'])
            ->filter(fn (array $band) => $band['strong'])
            ->pluck('label')
            ->all();

        $this->assertSame(['70–79', '80–89', '90–100'], $strong);
    }

    public function test_distribution_projects_the_share_if_the_band_below_crossed_over(): void
    {
        // 2 strong of 10 = 20%; the 3 fiches in 60–69 would take it to 50%.
        $this->createScored([72, 82, 62, 65, 69, 50, 40, 30, 20, 10]);

        $distribution = $this->insights()->distribution();

        $this->assertSame(10, $distribution['scored']);
        $this->assertSame(2, $distribution['strong']);
        $this->assertSame(3, $distribution['oneStepBelow']);
        $this->assertSame(50, $distribution['projectedShare']);
        $this->assertSame(70, $distribution['threshold']);
    }

    public function test_distribution_handles_an_empty_library(): void
    {
        $distribution = $this->insights()->distribution();

        $this->assertSame(0, $distribution['scored']);
        $this->assertNull($distribution['projectedShare']);
    }

    public function test_distribution_ignores_unpublished_and_unscored_fiches(): void
    {
        $this->createScored([72]);
        Fiche::factory()->withQualityScore(90)->create();
        Fiche::factory()->published()->create();

        $this->assertSame(1, $this->insights()->distribution()['scored']);
    }

    private function insights(): DiamantQualityInsights
    {
        return new DiamantQualityInsights;
    }

    /** @param  array<int, int>  $scores */
    private function createScored(array $scores, ?Carbon $createdAt = null): void
    {
        foreach ($scores as $score) {
            Fiche::factory()
                ->published()
                ->withQualityScore($score)
                ->create($createdAt ? ['created_at' => $createdAt] : []);
        }
    }
}
