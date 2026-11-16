<?php

namespace Tests\Feature\Pages;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Support\MilestoneCall\MilestoneCallContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeMilestoneBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['newsletter.milestone_call_target' => 10]);
    }

    /**
     * Seven published fiches spread over four published initiatives, plus
     * noise that must not count: unpublished fiches and an unpublished,
     * empty initiative.
     */
    private function seedCatalogue(): void
    {
        $rich = Initiative::factory()->published()->create(['title' => 'Rijk initiatief']);
        Fiche::factory()->published()->count(4)->for($rich)->create();
        Fiche::factory()->count(2)->for($rich)->create();

        Initiative::factory()->published()->create(['title' => 'Aardappeloogst']);
        Fiche::factory()->published()->for(Initiative::factory()->published()->create(['title' => 'Breicafé']))->create();
        Fiche::factory()->published()->count(2)->for(Initiative::factory()->published()->create(['title' => 'Croque-avond']))->create();

        Initiative::factory()->create(['title' => 'Verborgen initiatief']);
    }

    public function test_flag_off_shows_the_regular_initiatives_block(): void
    {
        config(['newsletter.homepage_milestone_active' => false]);
        $this->seedCatalogue();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Inspiratie om');
        $response->assertDontSee('Bijna 10 activiteiten');
        $this->assertNull($response->viewData('milestone'));
    }

    public function test_flag_on_shows_the_countdown_instead_of_the_initiatives_block(): void
    {
        config(['newsletter.homepage_milestone_active' => true]);
        $this->seedCatalogue();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Inspiratie om');
        $response->assertSee('Bijna <span class="text-[var(--color-primary)]">10 activiteiten</span>!', false);
        $response->assertSee('7 ideeën van collega\'s staan klaar. Nog 3 en de teller staat op 10.', false);
        $response->assertSeeInOrder(['Aardappeloogst', 'Breicafé', 'Croque-avond']);
        $response->assertSee('0 fiches');
        $response->assertSee('1 fiche');
        $response->assertSee(route('fiches.create'));
    }

    public function test_countdown_ignores_unpublished_fiches_and_initiatives(): void
    {
        config(['newsletter.homepage_milestone_active' => true]);
        $this->seedCatalogue();

        $milestone = $this->get(route('home'))->viewData('milestone');

        $this->assertSame(7, $milestone->fichesCount);
        $this->assertSame(3, $milestone->remaining());
        $this->assertNotContains('Verborgen initiatief', $milestone->needsLove->pluck('title'));
    }

    public function test_countdown_shows_exactly_three_of_the_thinnest_initiatives(): void
    {
        config(['newsletter.homepage_milestone_active' => true]);
        $this->seedCatalogue();

        $html = $this->blade('<x-home-milestone :content="$content" />', [
            'content' => MilestoneCallContent::build(),
        ]);

        $html->assertSeeInOrder(['Aardappeloogst', 'Breicafé', 'Croque-avond']);
        $html->assertDontSee('Rijk initiatief');
    }

    public function test_one_remaining_reads_nog_1(): void
    {
        config(['newsletter.milestone_call_target' => 8]);
        $this->seedCatalogue();

        $html = $this->blade('<x-home-milestone :content="$content" />', [
            'content' => MilestoneCallContent::build(),
        ]);

        $html->assertSee('Nog 1 en de teller staat op 8.');
    }

    public function test_reached_shows_a_sober_thank_you_without_thin_initiatives(): void
    {
        config([
            'newsletter.homepage_milestone_active' => true,
            'newsletter.milestone_call_target' => 5,
        ]);
        $this->seedCatalogue();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('5 activiteiten, gedeeld door jullie');
        $response->assertSee('Bedankt aan iedereen die een idee deelde.');
        $response->assertDontSee('Bijna 5 activiteiten');
        $response->assertDontSee('Vooral hier is jouw idee welkom');
        $response->assertDontSee('Inspiratie om');
        $response->assertSee(route('initiatives.index'));
    }

    public function test_no_variant_promises_a_party(): void
    {
        Initiative::factory()->published()->create(['title' => 'Kaartnamiddag']);

        foreach ([10, 0] as $target) {
            config(['newsletter.milestone_call_target' => $target]);

            $html = (string) $this->blade('<x-home-milestone :content="$content" />', [
                'content' => MilestoneCallContent::build(),
            ]);

            $this->assertDoesNotMatchRegularExpression('/feest|vier(en|t)|confetti|🎉/iu', $html);
        }
    }
}
