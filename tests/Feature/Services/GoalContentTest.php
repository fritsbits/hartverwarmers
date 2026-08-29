<?php

namespace Tests\Feature\Services;

use App\Services\GoalContent;
use Tests\TestCase;

class GoalContentTest extends TestCase
{
    public function test_an_unknown_goal_returns_empty_lists(): void
    {
        $content = GoalContent::for('bestaat-niet');

        $this->assertSame([
            'schoolvoorbeelden' => [],
            'verhalen' => [],
            'klassiekers' => [],
            'referenties' => [],
            'principes' => [],
        ], $content);
    }

    public function test_doen_lists_its_four_principes_in_legend_order(): void
    {
        $principes = GoalContent::for('doen')['principes'];

        $this->assertSame(
            ['Zelf doen', 'Kleine stapjes', 'Waardering', 'Aangepast materiaal'],
            array_column($principes, 'naam')
        );

        foreach ($principes as $principe) {
            $this->assertArrayHasKey('naam', $principe);
            $this->assertArrayHasKey('toelichting', $principe);
            $this->assertNotEmpty($principe['toelichting']);
        }
    }

    public function test_a_goal_without_principes_gets_an_empty_list(): void
    {
        $this->assertSame([], GoalContent::for('inclusief')['principes']);
    }

    /**
     * De legende op het papier en de chips op de klassiekerrijen tonen dezelfde
     * vier namen. Drijft een principe uit een verschuiving weg van de legende,
     * dan valt die herkenning stil en is het hele blok zinloos.
     */
    public function test_every_principe_in_a_verschuiving_appears_in_the_legend(): void
    {
        $content = GoalContent::for('doen');
        $namen = array_column($content['principes'], 'naam');

        foreach ($content['klassiekers'] as $klassieker) {
            foreach ($klassieker['verschuivingen'] as $verschuiving) {
                $this->assertContains($verschuiving['principe'], $namen);
            }
        }
    }

    public function test_doen_has_content_for_every_block(): void
    {
        $content = GoalContent::for('doen');

        $this->assertCount(3, $content['schoolvoorbeelden']);
        $this->assertCount(3, $content['verhalen']);
        $this->assertCount(3, $content['klassiekers']);
        $this->assertCount(5, $content['referenties']);
    }

    public function test_every_klassieker_has_the_fields_the_block_needs(): void
    {
        foreach (GoalContent::for('doen')['klassiekers'] as $klassieker) {
            $this->assertArrayHasKey('titel', $klassieker);
            $this->assertArrayHasKey('icoon', $klassieker);
            $this->assertArrayHasKey('kleur', $klassieker);
            $this->assertArrayHasKey('klassiek', $klassieker);
            $this->assertNotEmpty($klassieker['verschuivingen']);

            $this->assertContains($klassieker['icoon'], config('fiche-icons.allowlist'));
            $this->assertArrayHasKey($klassieker['kleur'], config('fiche-icons.colors'));

            foreach ($klassieker['verschuivingen'] as $verschuiving) {
                $this->assertArrayHasKey('voorbeeld', $verschuiving);
                $this->assertArrayHasKey('principe', $verschuiving);
                $this->assertArrayHasKey('toelichting', $verschuiving);
            }
        }
    }

    public function test_a_slug_cannot_escape_the_doelen_folder(): void
    {
        $this->assertSame([], GoalContent::for('../../updates')['klassiekers']);
    }
}
