<?php

namespace Tests\Feature\Pages;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use App\Support\PreviewMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class GoalPagePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Feature::define('diamant-goals', true);
    }

    private function previewing(): self
    {
        return $this->withSession([PreviewMode::SESSION_KEY => true]);
    }

    /**
     * Fakes the content disk and writes the given content as the goal's JSON file,
     * so a test can control exactly what `GoalContent::for()` returns.
     */
    private function mockGoalContent(string $facetSlug, array $content): void
    {
        Storage::fake('content');
        Storage::disk('content')->put("doelen/{$facetSlug}.json", json_encode($content));
    }

    public function test_the_klassiekers_block_is_hidden_without_preview(): void
    {
        $this->get('/doelen/doen')
            ->assertOk()
            ->assertDontSee('Zo maak je een diamantje van een klassieker');
    }

    public function test_the_klassiekers_block_shows_all_three_with_preview(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Zo maak je een diamantje van een klassieker')
            ->assertSee('De klassieke wandeling')
            ->assertSee('De klassieke knutselnamiddag')
            ->assertSee('Het klassieke koffiemoment')
            ->assertSee('Louis duwt zelf een stuk van de weg.')
            ->assertSee('Zelf doen')
            ->assertSee('Eén mogelijke versie. Niet de juiste.', false);
    }

    public function test_a_goal_without_content_shows_no_klassiekers(): void
    {
        $this->previewing()
            ->get('/doelen/talent')
            ->assertOk()
            ->assertDontSee('Zo maak je een diamantje van een klassieker');
    }

    public function test_a_klassieker_missing_icon_and_shifts_still_renders(): void
    {
        $this->mockGoalContent('doen', [
            'klassiekers' => [
                [
                    'titel' => 'De klassieke wandeling',
                    'kleur' => 2,
                    'klassiek' => 'De route ligt vast en de begeleidster duwt.',
                ],
            ],
        ]);

        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('De klassieke wandeling');
    }

    public function test_the_stories_show_in_preview(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Verhalen uit de praktijk')
            ->assertSee('Wie leerde jou eigenlijk zwemmen?', false)
            ->assertSee('Het volledige verhaal moet nog opgetekend worden.');
    }

    public function test_the_stories_are_hidden_without_preview(): void
    {
        $this->get('/doelen/doen')
            ->assertOk()
            ->assertDontSee('Verhalen uit de praktijk');
    }

    public function test_the_active_story_dot_exposes_its_state_to_assistive_tech(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee(':aria-current="actief === 0 ? \'true\' : \'false\'"', false)
            ->assertSee(':aria-current="actief === 1 ? \'true\' : \'false\'"', false)
            ->assertSee(':aria-current="actief === 2 ? \'true\' : \'false\'"', false)
            ->assertSee('Wie leerde jou eigenlijk zwemmen?', false)
            ->assertSee('Hij poetste elke fiets van het huis.', false)
            ->assertSee('Wiske zong al jaren niet meer.', false);
    }

    public function test_the_references_show_in_preview(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Wil je dieper graven rond doen?')
            ->assertSee('Studio Bomma over participatie')
            ->assertSee('Gesprekskaarten en spelmateriaal')
            ->assertSee('<span class="referentie-type">podcast</span>', false);
    }

    public function test_the_highlighted_fiches_show_in_preview(): void
    {
        $fiche = Fiche::factory()->create([
            'title' => 'Zelf de soep opdienen',
            'published' => true,
        ]);

        $this->mockGoalContent('doen', [
            'schoolvoorbeelden' => [
                ['fiche_id' => $fiche->id, 'waarom' => 'Zij schept zelf op, niemand doet het voor haar.'],
            ],
            'verhalen' => [],
            'klassiekers' => [],
            'referenties' => [],
        ]);

        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Zo ziet doen eruit')
            ->assertSee('Zelf de soep opdienen')
            ->assertSee('Zij schept zelf op, niemand doet het voor haar.');
    }

    public function test_a_fiche_without_an_initiative_is_skipped(): void
    {
        $fiche = Fiche::factory()->create([
            'title' => 'Losse fiche zonder initiatief',
            'published' => true,
            'initiative_id' => null,
        ]);

        $this->mockGoalContent('doen', [
            'schoolvoorbeelden' => [
                ['fiche_id' => $fiche->id, 'waarom' => 'Zou de route doen crashen.'],
            ],
            'verhalen' => [],
            'klassiekers' => [],
            'referenties' => [],
        ]);

        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertDontSee('Losse fiche zonder initiatief');
    }

    public function test_a_missing_fiche_is_skipped_without_an_error(): void
    {
        $this->mockGoalContent('doen', [
            'schoolvoorbeelden' => [
                ['fiche_id' => 999999, 'waarom' => 'Deze fiche bestaat niet meer.'],
            ],
            'verhalen' => [],
            'klassiekers' => [],
            'referenties' => [],
        ]);

        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertDontSee('Deze fiche bestaat niet meer.');
    }

    public function test_the_old_initiatives_block_is_hidden_in_preview(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Doen', 'slug' => 'doel-doen']);
        $initiative = Initiative::factory()->published()->create();
        $initiative->tags()->attach($goalTag);

        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertDontSee('Gebruik deze als startpunt en pas ze aan voor jouw bewoners.');
    }

    public function test_the_old_initiatives_block_stays_without_preview(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Doen', 'slug' => 'doel-doen']);
        $initiative = Initiative::factory()->published()->create();
        $initiative->tags()->attach($goalTag);

        $this->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Gebruik deze als startpunt en pas ze aan voor jouw bewoners.');
    }

    public function test_a_goal_without_content_keeps_its_initiatives_block_in_preview(): void
    {
        $goalTag = Tag::factory()->goal()->create(['name' => 'Inclusief', 'slug' => 'doel-inclusief']);
        $initiative = Initiative::factory()->published()->create();
        $initiative->tags()->attach($goalTag);

        $this->previewing()
            ->get('/doelen/inclusief')
            ->assertOk()
            ->assertSee('Gebruik deze als startpunt en pas ze aan voor jouw bewoners.')
            ->assertSee($initiative->title);
    }

    public function test_a_goal_without_content_shows_no_empty_cream_band_in_preview(): void
    {
        $this->previewing()
            ->get('/doelen/inclusief')
            ->assertOk()
            ->assertDontSee('gap-16 items-start', false);
    }

    public function test_the_klassiekers_gem_uses_the_facet_letter_not_a_hardcoded_d(): void
    {
        $this->mockGoalContent('inclusief', [
            'klassiekers' => [
                [
                    'titel' => 'De klassieke bingo',
                    'kleur' => 1,
                    'klassiek' => 'Iedereen speelt mee, maar niet iedereen kan de kaartjes lezen.',
                    'verschuivingen' => [
                        ['voorbeeld' => 'Grote kaartjes met symbolen.', 'principe' => 'Toegankelijk', 'toelichting' => 'Iedereen kan meedoen.'],
                    ],
                ],
            ],
            'verhalen' => [],
            'referenties' => [],
            'schoolvoorbeelden' => [],
        ]);

        $response = $this->previewing()->get('/doelen/inclusief');

        $response->assertOk()->assertSee('De klassieke bingo');

        // De klassiekers-gem is de enige `size="xxs"` (font-size 40) gem op deze
        // pagina; de navigatie en verwante-doelenlijst gebruiken andere maten.
        $this->assertMatchesRegularExpression(
            '/font-size="40"[^>]*>I<\/text>/',
            $response->getContent(),
            'Verwachtte de I-letter (Inclusief) op de klassiekers-gem, niet een hardgecodeerde D.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/font-size="40"[^>]*>D<\/text>/',
            $response->getContent()
        );
    }
}
