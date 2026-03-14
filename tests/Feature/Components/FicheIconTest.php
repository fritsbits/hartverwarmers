<?php

namespace Tests\Feature\Components;

use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_lucide_icon_when_icon_is_set(): void
    {
        $fiche = Fiche::factory()->withIcon('music')->create();

        $view = $this->blade('<x-fiche-icon :fiche="$fiche" />', ['fiche' => $fiche]);

        // The dynamic component renders an SVG (not the component tag name)
        $view->assertSee('<svg', false);
        // Should NOT contain the fallback document icon path
        $view->assertDontSee('M19.5 14.25v-2.625', false);
    }

    public function test_renders_fallback_when_icon_is_null(): void
    {
        $fiche = Fiche::factory()->create(['icon' => null]);

        $view = $this->blade('<x-fiche-icon :fiche="$fiche" />', ['fiche' => $fiche]);

        // Fallback should NOT render a Lucide component
        $view->assertDontSee('lucide-', false);
        // But should render an SVG
        $view->assertSee('<svg', false);
    }

    public function test_assigns_deterministic_color(): void
    {
        $fiche = Fiche::factory()->withIcon('music')->create();

        $view = $this->blade('<x-fiche-icon :fiche="$fiche" />', ['fiche' => $fiche]);

        $colors = config('fiche-icons.colors');
        $expected = $colors[$fiche->id % 6];
        $view->assertSee($expected['bg'], false);
    }

    public function test_renders_different_sizes(): void
    {
        $fiche = Fiche::factory()->withIcon('heart')->create();

        $viewSm = $this->blade('<x-fiche-icon :fiche="$fiche" size="sm" />', ['fiche' => $fiche]);
        $viewSm->assertSee('w-8', false);

        $viewLg = $this->blade('<x-fiche-icon :fiche="$fiche" size="lg" />', ['fiche' => $fiche]);
        $viewLg->assertSee('w-16', false);
    }
}
