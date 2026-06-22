<?php

namespace Tests\Unit\Support;

use App\Models\Fiche;
use App\Models\Theme;
use App\Models\ThemeOccurrence;
use App\Models\User;
use App\Support\Reactivation\ReactivationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactivationContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_reports_live_activity_and_contributor_counts(): void
    {
        $author = User::factory()->create();
        Fiche::factory()->count(3)->create(['user_id' => $author->id]);
        User::factory()->create(); // a non-contributor — must not be counted

        $content = ReactivationContent::build();

        $this->assertSame(3, $content->fichesCount);
        $this->assertSame(1, $content->contributorsCount);
        $this->assertNotNull($content->themes);
    }

    public function test_build_only_includes_upcoming_themes_that_have_published_activities(): void
    {
        $withActivities = Theme::factory()->create(['title' => 'Muziek en herinnering']);
        $withActivities->fiches()->attach(Fiche::factory()->published()->count(2)->create());
        ThemeOccurrence::factory()->for($withActivities)->create([
            'start_date' => now()->addDays(5)->toDateString(),
        ]);

        $empty = Theme::factory()->create(['title' => 'Leeg thema']);
        ThemeOccurrence::factory()->for($empty)->create([
            'start_date' => now()->addDays(6)->toDateString(),
        ]);

        $titles = ReactivationContent::build()->themes->map(fn ($occurrence) => $occurrence->theme->title);

        $this->assertTrue($titles->contains('Muziek en herinnering'));
        $this->assertFalse($titles->contains('Leeg thema'), 'a theme with no published activities must not render');
    }
}
