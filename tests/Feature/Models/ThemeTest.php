<?php

namespace Tests\Feature\Models;

use App\Enums\ThemeRecurrenceRule;
use App\Models\Theme;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_theme_via_factory(): void
    {
        $theme = Theme::factory()->create([
            'title' => 'Wereldyogadag',
            'slug' => 'wereldyogadag',
        ]);

        $this->assertDatabaseHas('themes', [
            'slug' => 'wereldyogadag',
            'title' => 'Wereldyogadag',
        ]);
        $this->assertInstanceOf(ThemeRecurrenceRule::class, $theme->recurrence_rule);
        $this->assertFalse($theme->is_month);
    }

    public function test_slug_is_unique(): void
    {
        Theme::factory()->create(['slug' => 'dup']);

        $this->expectException(QueryException::class);
        Theme::factory()->create(['slug' => 'dup']);
    }

    public function test_season_state(): void
    {
        $theme = Theme::factory()->season()->create();

        $this->assertTrue($theme->is_month);
    }
}
