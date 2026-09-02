<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bewaakt de redactionele regels voor themabeschrijvingen op de themapagina.
 *
 * De beschrijvingen zijn UI-copy voor animatoren, geen encyclopedie. Deze test
 * vangt de twee manieren waarop ze eerder ontspoord zijn: generieke AI-formules
 * en teksten die hard afgekapt werden op 300 tekens.
 */
class ThemeDescriptionStyleTest extends TestCase
{
    private const MAX_WOORDEN = 30;

    /** Formules die de dag uitleggen in plaats van de animator aanspreken. */
    private const VERBODEN_FORMULES = [
        'zetten we ons in',
        'ons inzetten',
        'engageren we ons',
        'is een dag waarop',
        'is een periode waarin',
        'vieren we de',
        'staan we stil bij het belang',
        'Deze dag is bedoeld',
        'Verenigde Naties',
        'ingevoerd door',
        'is gebaseerd op',
        'vernoemd naar',
    ];

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
    public function test_beschrijving_volgt_de_huisstijl(array $thema): void
    {
        $beschrijving = trim((string) ($thema['description'] ?? ''));

        if ($beschrijving === '') {
            $this->markTestSkipped("Thema '{$thema['title']}' heeft nog geen beschrijving.");
        }

        $this->assertLessThanOrEqual(
            self::MAX_WOORDEN,
            str_word_count($beschrijving, 0, 'áàäâéèëêíìïîóòöôúùüûçñÁÀÄÂÉÈËÊÍÌÏÎÓÒÖÔÚÙÜÛÇÑ’\'-'),
            "'{$thema['title']}' is te lang. Houd het onder de ".self::MAX_WOORDEN.' woorden.'
        );

        $this->assertMatchesRegularExpression(
            '/[.!?]$/u',
            $beschrijving,
            "'{$thema['title']}' eindigt niet op een leesteken. Waarschijnlijk afgekapt."
        );

        $this->assertStringStartsNotWith(
            mb_strtolower($thema['title']),
            mb_strtolower($beschrijving),
            "'{$thema['title']}' herhaalt zijn eigen titel. De kop staat er al boven."
        );

        foreach (self::VERBODEN_FORMULES as $formule) {
            $this->assertStringNotContainsStringIgnoringCase(
                $formule,
                $beschrijving,
                "'{$thema['title']}' bevat de formule '{$formule}'. Schrijf het als een animator."
            );
        }

        $this->assertDoesNotMatchRegularExpression(
            '/\b(1[89]\d\d|20\d\d)\b/',
            $beschrijving,
            "'{$thema['title']}' bevat een jaartal. Geen encyclopedie op de themapagina."
        );
    }
}
