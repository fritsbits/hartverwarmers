<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReorganizeDiverseInitiativeMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roll back our migration so we can test it
        Artisan::call('migrate:rollback', ['--step' => 1, '--no-interaction' => true]);
    }

    /**
     * Create the "Diverse" initiative and target initiatives, then seed fiches.
     *
     * @return array{diverse: Initiative, fiches: array<string, Fiche>}
     */
    private function seedDiverseData(): array
    {
        $user = User::factory()->create();

        $diverse = Initiative::factory()->published()->create([
            'title' => 'Diverse',
            'slug' => 'diverse',
            'created_by' => $user->id,
        ]);

        // Create all target initiatives
        foreach ([
            'raadspellen' => 'Raadspellen',
            'bordspellen' => 'Bordspellen',
            'herinneringen-delen' => 'Herinneringen delen',
            'fotos-herinneringen' => "Foto's & herinneringen",
            'samen-koken' => 'Samen koken',
            'zorg-verzorging' => 'Zorg & verzorging',
            'gesprekken-voeren' => 'Gesprekken voeren',
            'beweging-fit' => 'Beweging & fit',
        ] as $slug => $title) {
            Initiative::factory()->published()->create([
                'title' => $title,
                'slug' => $slug,
            ]);
        }

        // Create fiches in Diverse with the specific IDs the migration expects.
        // We use DB::table to control the IDs.
        $ficheData = [
            // Team support (9)
            590 => 'Bedank de medewerkers',
            578 => 'Bespreek mentale veerkracht met je team',
            587 => 'Bied meeneemmaaltijden aan voor medewerkers',
            586 => 'Boodschappen bestellen op het werk',
            577 => 'Doe een snelcursus psychosociale opvang',
            581 => 'Fris verpleegkundige kennis op',
            575 => 'Nodig een vertrouwenspersoon uit voor medewerkers',
            588 => 'Maak een overzicht van alle steunbetuigingen',
            591 => 'Maak een apart email adres voor boodschappen',
            // Mantelzorg (3)
            571 => 'Geef mantelzorgers tips',
            582 => 'Geef zelfzorgopdrachten aan mantelzorgers',
            584 => 'Stem de zorg af',
            // Spelletjes (3)
            569 => 'Visspel',
            568 => 'Wat is er fout?',
            567 => 'Spelnamiddag',
            // Herinneringen (3)
            570 => 'De koffer van je leven',
            574 => 'Verzamel levensverhalen met families',
            576 => 'Geef virtuele rondleiding legerdienst',
            // Overig (7)
            572 => 'High tea afternoon',
            589 => 'Doe cursus handen wassen',
            573 => 'Gebruik Zilverwijzer',
            579 => 'Organiseer een carwash',
            585 => 'Inspireer activiteiten aan het raam',
            583 => 'Neem deel aan #hallovanhier',
            580 => 'Stel een verrassingspakket samen',
        ];

        $fiches = [];
        foreach ($ficheData as $id => $title) {
            DB::table('fiches')->insert([
                'id' => $id,
                'initiative_id' => $diverse->id,
                'user_id' => $user->id,
                'title' => $title,
                'slug' => \Str::slug($title).'-'.$id,
                'description' => 'Test beschrijving',
                'published' => true,
                'has_diamond' => false,
                'download_count' => 0,
                'kudos_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['diverse' => $diverse];
    }

    public function test_migration_creates_teamondersteuning_initiative(): void
    {
        $this->seedDiverseData();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $initiative = Initiative::where('slug', 'teamondersteuning')->first();
        $this->assertNotNull($initiative);
        $this->assertEquals('Teamondersteuning', $initiative->title);
        $this->assertTrue((bool) $initiative->published);
    }

    public function test_migration_moves_team_fiches_to_teamondersteuning(): void
    {
        $this->seedDiverseData();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $initiative = Initiative::where('slug', 'teamondersteuning')->first();
        $ficheIds = $initiative->fiches()->pluck('id')->toArray();

        $this->assertCount(9, $ficheIds);
        foreach ([590, 578, 587, 586, 577, 581, 575, 588, 591] as $id) {
            $this->assertContains($id, $ficheIds);
        }
    }

    public function test_migration_moves_mantelzorg_fiches_to_zorg_verzorging(): void
    {
        $this->seedDiverseData();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $zorg = Initiative::where('slug', 'zorg-verzorging')->first();
        $ficheIds = $zorg->fiches()->pluck('id')->toArray();

        foreach ([571, 582, 584] as $id) {
            $this->assertContains($id, $ficheIds);
        }
    }

    public function test_migration_soft_deletes_diverse(): void
    {
        $this->seedDiverseData();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $this->assertNull(Initiative::where('slug', 'diverse')->first());
        $this->assertNotNull(Initiative::withTrashed()->where('slug', 'diverse')->first());
    }

    public function test_migration_leaves_no_fiches_in_diverse(): void
    {
        $data = $this->seedDiverseData();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $this->assertEquals(0, Fiche::where('initiative_id', $data['diverse']->id)->count());
    }

    public function test_migration_moves_all_25_fiches_to_correct_destinations(): void
    {
        $this->seedDiverseData();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $expected = [
            'teamondersteuning' => [590, 578, 587, 586, 577, 581, 575, 588, 591],
            'zorg-verzorging' => [571, 582, 584, 589, 580],
            'raadspellen' => [569, 568],
            'bordspellen' => [567],
            'herinneringen-delen' => [570, 574, 576],
            'samen-koken' => [572],
            'gesprekken-voeren' => [573, 585],
            'beweging-fit' => [579],
            'fotos-herinneringen' => [583],
        ];

        foreach ($expected as $slug => $expectedIds) {
            $initiative = Initiative::where('slug', $slug)->first();
            $ficheIds = $initiative->fiches()->pluck('id')->toArray();

            foreach ($expectedIds as $id) {
                $this->assertContains($id, $ficheIds, "Fiche $id should be in initiative '$slug'");
            }
        }
    }

    public function test_migration_is_safe_when_diverse_does_not_exist(): void
    {
        // Don't seed any data — Diverse doesn't exist
        Artisan::call('migrate', ['--no-interaction' => true]);

        // Should not crash and should not create Teamondersteuning
        $this->assertNull(Initiative::where('slug', 'teamondersteuning')->first());
    }
}
