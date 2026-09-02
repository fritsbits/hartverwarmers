<?php

namespace Tests\Feature\Components;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_fiche_shows_its_moment_on_the_initiative_page(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 2, 14, 0, 0, 'Europe/Brussels'));

        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->for($initiative)->create([
            'published' => true,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertOk();
        $response->assertSee('fiche-date-fresh', false);
        $response->assertSeeText('gisteren');
    }

    public function test_an_older_fiche_falls_back_to_a_quiet_month_name(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 2, 14, 0, 0, 'Europe/Brussels'));

        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->for($initiative)->create([
            'published' => true,
            'created_at' => now()->subMonths(2),
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertOk();
        $response->assertSee('fiche-date-quiet', false);
        $response->assertSeeText('juli');
        $response->assertDontSeeText('2 maanden geleden');
    }

    public function test_the_initiative_list_no_longer_repeats_the_organisation(): void
    {
        $initiative = Initiative::factory()->published()->create();
        $user = User::factory()->create([
            'first_name' => 'Bea',
            'last_name' => 'Torrelle',
            'organisation' => 'WZC Sint-Vincentius',
        ]);
        Fiche::factory()->for($initiative)->for($user)->create(['published' => true]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertOk();
        $response->assertSeeText('Bea Torrelle');
        $response->assertDontSeeText('Bea Torrelle, WZC Sint-Vincentius');
    }

    public function test_the_contributor_page_replaces_the_raw_month_stamp(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 2, 14, 0, 0, 'Europe/Brussels'));

        $contributor = User::factory()->create();
        $initiative = Initiative::factory()->published()->create(['title' => 'Quiz']);
        Fiche::factory()->for($initiative)->for($contributor)->create([
            'published' => true,
            'created_at' => now()->subMonths(2),
        ]);

        $response = $this->get(route('contributors.show', $contributor));

        $response->assertOk();
        $response->assertSee('fiche-date-quiet', false);
        $response->assertSeeText('juli');
        $response->assertDontSeeText('jul. 2026');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
