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
            ->assertDontSee('Waar zit Doen in een gewone wandeling?');
    }

    public function test_the_klassiekers_block_shows_all_three_with_preview(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Klassiekers')
            ->assertSee('Waar zit <em>Doen</em> in een gewone wandeling?', false)
            ->assertSee('Die vier principes komen terug in elke activiteit die je al draait.')
            ->assertSee('Drie kleine verschuivingen. Je hoeft er geen nieuwe activiteit voor te bedenken.')
            ->assertSee('De klassieke wandeling')
            ->assertSee('De klassieke knutselnamiddag')
            ->assertSee('Het klassieke koffiemoment')
            ->assertSee('Louis duwt zelf een stuk van de weg.')
            ->assertSee('Eén mogelijke versie. Niet de juiste.', false);
    }

    public function test_the_old_klassiekers_copy_is_gone(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertDontSee('Zo maak je een diamantje van een klassieker')
            ->assertDontSee('Zo kan het schitteren')
            ->assertDontSee('diamantje van een klassieker');
    }

    /**
     * Ingeklapt moet de rij allebei de helften van het paar tonen: de klassieke
     * versie achter NIET en de principes achter WEL. Staat het WEL-deel alleen
     * in de uitgeklapte body, dan leest de rij als een aanklacht zonder hulp.
     */
    public function test_a_collapsed_klassieker_shows_its_principes_as_chips(): void
    {
        $response = $this->previewing()->get('/doelen/doen')->assertOk();

        $this->assertSame(
            9,
            substr_count($response->getContent(), 'class="klassieker-chip"'),
            'Verwachtte drie principechips op elk van de drie ingeklapte rijen.'
        );

        // Het koffiemoment verschuift op Aangepast materiaal, Zelf doen en Kleine
        // stapjes; de chips staan in legendevolgorde, niet in die van de content.
        $this->assertMatchesRegularExpression(
            '/Het klassieke koffiemoment.*?Zelf doen.*?Kleine stapjes.*?Aangepast materiaal/s',
            $response->getContent()
        );
    }

    public function test_no_klassieker_starts_open(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertDontSee('<details class="klassieker" open', false);
    }

    public function test_the_toggle_carries_both_labels_so_it_works_without_js(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Toon 3 verschuivingen')
            ->assertSee('Toon minder');
    }

    public function test_a_goal_without_content_shows_no_klassiekers(): void
    {
        $this->previewing()
            ->get('/doelen/talent')
            ->assertOk()
            ->assertDontSee('Waar zit Talent in een gewone wandeling?');
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
            ->assertSee('Wil je dieper graven rond <em>Doen</em>?', false)
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
            ->assertSee('Zo ziet <em>Doen</em> eruit', false)
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

        // Eén edelsteen: de legende en de chips delen dezelfde omlijnde ruit, en
        // de massieve letterruit die hier vroeger stond is weg. Die droeg een
        // hardgecodeerde D, dus ook op Inclusief.
        $this->assertDoesNotMatchRegularExpression(
            '/font-size="40"[^>]*>[A-Z]<\/text>/',
            $response->getContent(),
            'De klassiekerrij hoort de gedeelde principe-ruit te gebruiken, geen letterruit.'
        );
        $this->assertStringContainsString('class="principe-gem"', $response->getContent());
    }

    public function test_the_paper_becomes_the_principe_legend_in_preview(): void
    {
        $this->previewing()
            ->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Checklist')
            ->assertSee('ook als het trager gaat')
            ->assertSee('zo blijft zelf doen mogelijk')
            ->assertDontSee('Bouw activiteiten op in kleine stapjes');
    }

    public function test_the_paper_keeps_its_checklist_without_preview(): void
    {
        $this->get('/doelen/doen')
            ->assertOk()
            ->assertSee('Checklist')
            ->assertSee('Bouw activiteiten op in kleine stapjes')
            ->assertDontSee('ook als het trager gaat');
    }

    /**
     * Het papier stond twee keer in de bron, byte-identiek op de wrapper na.
     */
    public function test_the_paper_is_rendered_once(): void
    {
        $response = $this->previewing()->get('/doelen/doen')->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'quote-paper-lg'));
    }

    public function test_a_goal_without_principes_keeps_its_reflection_questions(): void
    {
        $this->previewing()
            ->get('/doelen/talent')
            ->assertOk()
            ->assertSee('Checklist')
            ->assertDontSee('ook als het trager gaat');
    }
}
