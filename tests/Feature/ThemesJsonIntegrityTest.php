<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bewaakt de afspraak over fiche_slugs in database/seeders/data/themes.json.
 *
 * Een ontbrekende sleutel betekent "nog te doen"; een lege array betekent
 * "bekeken, er hoort niets bij". Zonder deze test glipt een thema zonder
 * beslissing erdoor: themes:import slaat het over en themes:suggest-fiches
 * pakt het nooit meer op. Zie docs/research/themakalender-verval-plan.md,
 * faalvorm 4.
 */
class ThemesJsonIntegrityTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function themaProvider(): array
    {
        // Data providers draaien voor de app boot, dus base_path() bestaat hier nog niet.
        $pad = dirname(__DIR__, 2).'/database/seeders/data/themes.json';
        $data = json_decode(file_get_contents($pad), true);

        $cases = [];
        foreach ($data['themes'] as $thema) {
            $cases[$thema['slug']] = [$thema];
        }

        return $cases;
    }

    /**
     * @param  array<string, mixed>  $thema
     */
    #[DataProvider('themaProvider')]
    public function test_elk_thema_heeft_een_fiche_slugs_sleutel(array $thema): void
    {
        $this->assertArrayHasKey(
            'fiche_slugs',
            $thema,
            "'{$thema['title']}' heeft geen fiche_slugs-sleutel. Koppel fiches of zet bewust een lege array."
        );

        $this->assertIsArray(
            $thema['fiche_slugs'],
            "'{$thema['title']}' heeft een fiche_slugs die geen array is."
        );
    }
}
