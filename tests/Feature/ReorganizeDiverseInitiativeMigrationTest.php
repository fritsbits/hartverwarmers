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

        // Roll back the correction migration so we can test it
        Artisan::call('migrate:rollback', ['--step' => 1, '--no-interaction' => true]);
    }

    /**
     * Seed the state as if the old migration already ran:
     * "Diverse" is soft-deleted, "Teamondersteuning" exists, fiches in wrong places.
     *
     * @return array{diverse: Initiative, teamondersteuning: Initiative}
     */
    private function seedPostOldMigrationState(): array
    {
        $user = User::factory()->create();

        $diverse = Initiative::factory()->published()->create([
            'title' => 'Diverse',
            'slug' => 'diverse',
            'created_by' => $user->id,
            'deleted_at' => now(),
        ]);

        $teamondersteuning = Initiative::factory()->published()->create([
            'title' => 'Teamondersteuning',
            'slug' => 'teamondersteuning',
            'created_by' => $user->id,
        ]);

        // Create all target initiatives
        $targets = [];
        foreach ([
            'raadspellen' => 'Raadspellen',
            'bordspellen' => 'Bordspellen',
            'herinneringen-delen' => 'Herinneringen delen',
            'fotos-herinneringen' => "Foto's & herinneringen",
            'samen-koken' => 'Samen koken',
            'zorg-verzorging' => 'Zorg & verzorging',
            'gesprekken-voeren' => 'Gesprekken voeren',
            'beweging-fit' => 'Beweging & fit',
            'feesten-vieren' => 'Feesten vieren',
            'creatief-atelier' => 'Creatief atelier',
        ] as $slug => $title) {
            $targets[$slug] = Initiative::factory()->published()->create([
                'title' => $title,
                'slug' => $slug,
            ]);
        }

        // Simulate old migration results: fiches in wrong initiatives
        $wrongPlacements = [
            // These 9 went to Teamondersteuning, should go to Zorg & verzorging
            [$teamondersteuning->id, 'bedank-de-medewerkers'],
            [$teamondersteuning->id, 'bespreek-mentale-veerkracht-met-je-team'],
            [$teamondersteuning->id, 'bied-meeneemmaaltijden-aan-voor-medewerkers'],
            [$teamondersteuning->id, 'boodschappen-bestellen-op-het-werk'],
            [$teamondersteuning->id, 'doe-een-snelcursus-psychosociale-opvang'],
            [$teamondersteuning->id, 'fris-verpleegkundige-kennis-op'],
            [$teamondersteuning->id, 'nodig-een-vertrouwenspersoon-uit-voor-medewerkers'],
            [$teamondersteuning->id, 'maak-een-overzicht-van-alle-steunbetuigingen'],
            [$teamondersteuning->id, 'maak-een-apart-email-adres-voor-boodschappen-aan-bewoners'],
            // Correctly in Zorg & verzorging
            [$targets['zorg-verzorging']->id, 'geef-mantelzorgers-tips-om-de-zorg-vol-te-houden'],
            [$targets['zorg-verzorging']->id, 'geef-zelfzorgopdrachten-aan-mantelzorgers'],
            [$targets['zorg-verzorging']->id, 'stem-de-zorg-af-tussen-bewoner-mantelzorger-en-het-woonzorgcentrum'],
            [$targets['zorg-verzorging']->id, 'doe-cursus-handen-wassen'],
            // Wrong: Raadspellen, should be Bordspellen
            [$targets['raadspellen']->id, 'visspel'],
            // Correctly in Raadspellen
            [$targets['raadspellen']->id, 'wat-is-er-fout'],
            // Correctly in Bordspellen
            [$targets['bordspellen']->id, 'spelnamiddag-ik-zet-deze-er-nogmaals-op-omdat-men-deze-niet-kon-downloaden-hopelijk-nu-dan-meer-succes'],
            // Correctly in Herinneringen delen
            [$targets['herinneringen-delen']->id, 'de-koffer-van-je-leven'],
            [$targets['herinneringen-delen']->id, 'verzamel-levensverhalen-met-families'],
            // Wrong: Herinneringen delen, should be Foto's & herinneringen
            [$targets['herinneringen-delen']->id, 'geef-virtuele-rondleiding-legerdienst'],
            // Wrong: Samen koken, should be Feesten vieren
            [$targets['samen-koken']->id, 'high-tea-afternoon'],
            // Wrong: Gesprekken voeren, should be Zorg & verzorging
            [$targets['gesprekken-voeren']->id, 'gebruik-zilverwijzer-om-veerkracht-en-zelfmanagement-van-bewoners-te-versterken'],
            // Wrong: Beweging & fit, should be soft-deleted
            [$targets['beweging-fit']->id, 'organiseer-een-carwash'],
            // Wrong: Gesprekken voeren, should be Creatief atelier
            [$targets['gesprekken-voeren']->id, 'inspireer-activiteiten-aan-het-raam'],
            // Wrong: Foto's & herinneringen, should be Creatief atelier
            [$targets['fotos-herinneringen']->id, 'neem-deel-aan-hallovanhier-uitdagingen-op-sociale-media'],
            // Wrong: Zorg & verzorging, should be Feesten vieren
            [$targets['zorg-verzorging']->id, 'stel-een-verrassingspakket-samen'],
        ];

        foreach ($wrongPlacements as [$initiativeId, $slug]) {
            DB::table('fiches')->insert([
                'initiative_id' => $initiativeId,
                'user_id' => $user->id,
                'title' => ucfirst(str_replace('-', ' ', $slug)),
                'slug' => $slug,
                'description' => 'Test',
                'published' => true,
                'has_diamond' => false,
                'download_count' => 0,
                'kudos_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['diverse' => $diverse, 'teamondersteuning' => $teamondersteuning];
    }

    public function test_migration_moves_teamondersteuning_fiches_to_zorg_verzorging(): void
    {
        $this->seedPostOldMigrationState();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $zorg = Initiative::where('slug', 'zorg-verzorging')->first();
        $ficheSlugs = $zorg->fiches()->pluck('slug')->toArray();

        foreach ([
            'bedank-de-medewerkers',
            'bespreek-mentale-veerkracht-met-je-team',
            'bied-meeneemmaaltijden-aan-voor-medewerkers',
            'boodschappen-bestellen-op-het-werk',
            'doe-een-snelcursus-psychosociale-opvang',
            'fris-verpleegkundige-kennis-op',
            'nodig-een-vertrouwenspersoon-uit-voor-medewerkers',
            'maak-een-overzicht-van-alle-steunbetuigingen',
            'maak-een-apart-email-adres-voor-boodschappen-aan-bewoners',
            'geef-mantelzorgers-tips-om-de-zorg-vol-te-houden',
            'geef-zelfzorgopdrachten-aan-mantelzorgers',
            'stem-de-zorg-af-tussen-bewoner-mantelzorger-en-het-woonzorgcentrum',
            'doe-cursus-handen-wassen',
            'gebruik-zilverwijzer-om-veerkracht-en-zelfmanagement-van-bewoners-te-versterken',
        ] as $slug) {
            $this->assertContains($slug, $ficheSlugs, "Fiche '{$slug}' should be in Zorg & verzorging");
        }
    }

    public function test_migration_deletes_teamondersteuning_initiative(): void
    {
        $this->seedPostOldMigrationState();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $this->assertNull(Initiative::where('slug', 'teamondersteuning')->first());
        // Hard delete, not soft delete
        $this->assertNull(Initiative::withTrashed()->where('slug', 'teamondersteuning')->first());
    }

    public function test_migration_moves_visspel_to_bordspellen(): void
    {
        $this->seedPostOldMigrationState();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $bordspellen = Initiative::where('slug', 'bordspellen')->first();
        $this->assertTrue($bordspellen->fiches()->where('slug', 'visspel')->exists());
    }

    public function test_migration_moves_rondleiding_to_fotos_herinneringen(): void
    {
        $this->seedPostOldMigrationState();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $fotos = Initiative::where('slug', 'fotos-herinneringen')->first();
        $this->assertTrue($fotos->fiches()->where('slug', 'geef-virtuele-rondleiding-legerdienst')->exists());
    }

    public function test_migration_moves_high_tea_and_verrassingspakket_to_feesten_vieren(): void
    {
        $this->seedPostOldMigrationState();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $feesten = Initiative::where('slug', 'feesten-vieren')->first();
        $this->assertTrue($feesten->fiches()->where('slug', 'high-tea-afternoon')->exists());
        $this->assertTrue($feesten->fiches()->where('slug', 'stel-een-verrassingspakket-samen')->exists());
    }

    public function test_migration_moves_creative_fiches_to_creatief_atelier(): void
    {
        $this->seedPostOldMigrationState();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $creatief = Initiative::where('slug', 'creatief-atelier')->first();
        $this->assertTrue($creatief->fiches()->where('slug', 'inspireer-activiteiten-aan-het-raam')->exists());
        $this->assertTrue($creatief->fiches()->where('slug', 'neem-deel-aan-hallovanhier-uitdagingen-op-sociale-media')->exists());
    }

    public function test_migration_soft_deletes_carwash(): void
    {
        $this->seedPostOldMigrationState();

        Artisan::call('migrate', ['--no-interaction' => true]);

        $this->assertNull(Fiche::where('slug', 'organiseer-een-carwash')->first());
        $this->assertNotNull(Fiche::withTrashed()->where('slug', 'organiseer-een-carwash')->first());
    }

    public function test_migration_is_safe_when_diverse_does_not_exist(): void
    {
        // Don't seed — Diverse doesn't exist
        Artisan::call('migrate', ['--no-interaction' => true]);

        // Should not crash
        $this->assertNull(Initiative::where('slug', 'diverse')->first());
    }

    public function test_migration_is_idempotent_when_fiches_already_correct(): void
    {
        $user = User::factory()->create();

        // Create initiatives and fiches already in correct positions
        $zorg = Initiative::factory()->published()->create(['slug' => 'zorg-verzorging']);
        $feesten = Initiative::factory()->published()->create(['slug' => 'feesten-vieren']);

        DB::table('fiches')->insert([
            'initiative_id' => $zorg->id,
            'user_id' => $user->id,
            'title' => 'Bedank de medewerkers',
            'slug' => 'bedank-de-medewerkers',
            'description' => 'Test',
            'published' => true,
            'has_diamond' => false,
            'download_count' => 0,
            'kudos_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fiches')->insert([
            'initiative_id' => $feesten->id,
            'user_id' => $user->id,
            'title' => 'High tea afternoon',
            'slug' => 'high-tea-afternoon',
            'description' => 'Test',
            'published' => true,
            'has_diamond' => false,
            'download_count' => 0,
            'kudos_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate', ['--no-interaction' => true]);

        // Fiches should stay where they were
        $this->assertTrue($zorg->fiches()->where('slug', 'bedank-de-medewerkers')->exists());
        $this->assertTrue($feesten->fiches()->where('slug', 'high-tea-afternoon')->exists());
    }
}
