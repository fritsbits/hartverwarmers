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

    public function test_elke_occurrence_is_geldig_en_uniek(): void
    {
        $data = $this->themesJson();
        $themaSlugs = array_column($data['themes'], 'slug');
        $fouten = [];

        foreach ($this->occurrenceBlokken($data) as $blokJaar => $rijen) {
            $gezien = [];

            foreach ($rijen as $rij) {
                $slug = $rij['theme_slug'] ?? '(ontbreekt)';
                $label = "occurrences_{$blokJaar} / {$slug}";

                if (! in_array($slug, $themaSlugs, true)) {
                    $fouten[] = "{$label}: onbekend thema.";
                }

                if (isset($gezien[$slug])) {
                    $fouten[] = "{$label}: dubbele occurrence in hetzelfde jaar.";
                }
                $gezien[$slug] = true;

                if (($rij['year'] ?? null) !== $blokJaar) {
                    $fouten[] = "{$label}: year is ".json_encode($rij['year'] ?? null).", verwacht {$blokJaar}.";
                }

                $start = $this->datum($rij['start_date'] ?? null);
                if ($start === null) {
                    $fouten[] = "{$label}: ongeldige start_date.";

                    continue;
                }

                if ((int) $start->format('Y') !== $blokJaar) {
                    $fouten[] = "{$label}: start_date {$rij['start_date']} valt buiten {$blokJaar}.";
                }

                if (($rij['end_date'] ?? null) !== null) {
                    $einde = $this->datum($rij['end_date']);

                    if ($einde === null) {
                        $fouten[] = "{$label}: ongeldige end_date.";
                    } elseif ($einde < $start) {
                        $fouten[] = "{$label}: end_date {$rij['end_date']} ligt vóór start_date {$rij['start_date']}.";
                    }
                }
            }
        }

        $this->assertSame([], $fouten, implode("\n", $fouten));
    }

    public function test_2027_heeft_thema_s_in_alle_twaalf_maanden(): void
    {
        $maanden = [];

        foreach ($this->themesJson()['occurrences_2027'] ?? [] as $rij) {
            $maanden[(int) substr($rij['start_date'], 5, 2)] = true;
        }

        $ontbrekend = array_diff(range(1, 12), array_keys($maanden));

        $this->assertSame([], array_values($ontbrekend), 'Maanden zonder thema dat erin begint in 2027: '.implode(', ', $ontbrekend));
    }

    /**
     * Thema's waarvan de 2027-datum nog niet bevestigd is door een bron. Ze
     * krijgen bewust geen occurrence tot de organisator publiceert; zie
     * docs/research/themakalender-2027-bronnen.md. Haal een slug hier weg
     * zodra zijn datum in occurrences_2027 staat.
     */
    private const array OPEN_IN_2027 = [
        'dag-van-de-buren',
        'dag-van-de-verzorgenden-en-zorgkundigen',
        'dag-van-de-wetenschap',
        'french-fries-day',
        'nacht-van-de-duisternis',
        'nationale-frietjesdag',
        'nationale-kappersdag',
        'week-tegen-pijn',
        'week-van-de-geletterdheid',
        'week-van-de-ouderen',
        'week-van-smaak',
    ];

    public function test_elk_bevestigd_thema_heeft_precies_een_occurrence_in_2027(): void
    {
        $data = $this->themesJson();
        $aantallen = array_count_values(array_column($data['occurrences_2027'] ?? [], 'theme_slug'));
        $fouten = [];

        foreach (array_column($data['themes'], 'slug') as $slug) {
            $verwacht = in_array($slug, self::OPEN_IN_2027, true) ? 0 : 1;

            if (($aantallen[$slug] ?? 0) !== $verwacht) {
                $fouten[] = "{$slug}: ".($aantallen[$slug] ?? 0)." occurrence(s), verwacht {$verwacht}.";
            }
        }

        $this->assertSame([], $fouten, implode("\n", $fouten));
        $this->assertSame([], array_diff(self::OPEN_IN_2027, array_column($data['themes'], 'slug')), 'OPEN_IN_2027 noemt een onbekend thema.');
    }

    /**
     * Een open thema met een berekenbare regel zou bij themes:extend-occurrences
     * voor een volgend jaar stil een datum zonder bron terugkrijgen.
     */
    public function test_open_thema_s_hebben_een_regel_die_de_generator_niet_berekent(): void
    {
        $berekenbaar = array_values(array_filter(
            $this->themesJson()['themes'],
            fn (array $thema): bool => in_array($thema['slug'], self::OPEN_IN_2027, true)
                && in_array($thema['recurrence_rule'], ['fixed', 'nth_weekday', 'easter'], true),
        ));

        $this->assertSame([], array_column($berekenbaar, 'slug'), 'Open thema met berekenbare regel; zet ze op variable_annual.');
    }

    /**
     * Datums uit docs/research/themakalender-2027-bronnen.md die bij een
     * blinde herberekening uit de vorige jaargang fout zouden lopen.
     *
     * @return array<string, array{string, string, string|null}>
     */
    public static function bronbevestigdeDatumProvider(): array
    {
        return [
            'open monumentendag, tweede zondag van september' => ['open-monumentendag', '2027-09-12', null],
            'carnaval, zeven weken voor Pasen' => ['carnaval', '2027-02-07', '2027-02-09'],
            'Pasen' => ['pasen', '2027-03-28', null],
            'Hemelvaart' => ['onze-lieve-heer-hemelvaart', '2027-05-06', null],
            'Pinksteren' => ['pinksteren', '2027-05-16', null],
            'moederdag, tweede zondag van mei' => ['moederdag', '2027-05-09', null],
            'verloren maandag' => ['verloren-maandag', '2027-01-11', null],
            'poëzieweek' => ['poezieweek', '2027-01-28', '2027-02-03'],
            'week van de vrijwilliger' => ['week-van-de-vrijwilliger', '2027-02-27', '2027-03-07'],
            'dag van de zorg' => ['dag-van-de-zorg', '2027-03-20', '2027-03-21'],
            'erfgoeddag' => ['erfgoeddag', '2027-04-18', null],
            'krokusvakantie' => ['krokusvakantie', '2027-02-08', '2027-02-14'],
            'paasvakantie' => ['paasvakantie', '2027-03-29', '2027-04-11'],
            'herfstvakantie, één week' => ['herfstvakantie', '2027-11-01', '2027-11-07'],
            'begin lente' => ['lente', '2027-03-20', null],
            'begin herfst' => ['herfst', '2027-09-23', null],
            'begin winter' => ['winter', '2027-12-22', null],
            'ronde van frankrijk' => ['ronde-van-frankrijk', '2027-07-02', '2027-07-25'],
            'duurzame dinsdag, eerste dinsdag van september' => ['duurzame-dinsdag', '2027-09-07', null],
            'baarddag, eerste zaterdag van september' => ['baarddag', '2027-09-04', null],
            'dag van het bier, eerste vrijdag van augustus' => ['internationale-dag-van-het-bier', '2027-08-06', null],
            'wereld dovendag, laatste zaterdag van september' => ['wereld-dovendag', '2027-09-25', null],
            'lichtjesdag, tweede zondag van december' => ['lichtjesdag', '2027-12-12', null],
            'voorleesweek, laatste volledige week van november' => ['voorleesweek', '2027-11-20', '2027-11-28'],
        ];
    }

    #[DataProvider('bronbevestigdeDatumProvider')]
    public function test_bronbevestigde_datum_in_2027(string $slug, string $start, ?string $einde): void
    {
        $rij = collect($this->themesJson()['occurrences_2027'] ?? [])->firstWhere('theme_slug', $slug);

        $this->assertNotNull($rij, "{$slug} heeft geen occurrence in 2027.");
        $this->assertSame([$start, $einde], [$rij['start_date'], $rij['end_date'] ?? null], "{$slug} wijkt af van het bronverslag.");
    }

    /**
     * De intro's hangen aan een maandnummer en gelden elk jaar. Een feestdag
     * die rond Pasen schuift, valt het ene jaar in de ene maand en het andere
     * jaar in de volgende.
     */
    public function test_maandintros_noemen_geen_verschuivende_feestdagen(): void
    {
        $intros = json_decode(file_get_contents(base_path('resources/content/themes/monthly-intros.json')), true);

        $this->assertSame(range(1, 12), array_keys($intros), 'Elke maand heeft precies één intro.');

        foreach ($intros as $maand => $intro) {
            $this->assertDoesNotMatchRegularExpression(
                '/\b(pasen|paas\w*|hemelvaart|pinkster\w*|carnaval|aswoensdag|palmzondag)\b/iu',
                $intro['title'].' '.$intro['intro'],
                "De intro van maand {$maand} noemt een feestdag die van maand kan wisselen."
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function themesJson(): array
    {
        return json_decode(file_get_contents(base_path('database/seeders/data/themes.json')), true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, list<array<string, mixed>>>
     */
    private function occurrenceBlokken(array $data): array
    {
        $blokken = [];

        foreach ($data as $sleutel => $rijen) {
            if (preg_match('/^occurrences_(\d{4})$/', $sleutel, $match)) {
                $blokken[(int) $match[1]] = $rijen;
            }
        }

        return $blokken;
    }

    private function datum(mixed $waarde): ?\DateTimeImmutable
    {
        if (! is_string($waarde)) {
            return null;
        }

        $datum = \DateTimeImmutable::createFromFormat('!Y-m-d', $waarde);

        return $datum !== false && $datum->format('Y-m-d') === $waarde ? $datum : null;
    }
}
