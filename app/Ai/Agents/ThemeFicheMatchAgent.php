<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')]
class ThemeFicheMatchAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Je koppelt bestaande activiteitenfiches aan een kalenderthema voor animatoren in Vlaamse woonzorgcentra.

        Je krijgt één thema en een catalogus van fiches. Kies enkel de fiches die een animator
        op of rond die dag echt zou bovenhalen.

        Wat telt als een match:
        - De activiteit gaat over het onderwerp van de dag, of sluit er inhoudelijk op aan.
        - Een animator die het thema wil invullen, heeft aan deze fiche genoeg om te starten.

        Wat geen match is:
        - Een losse woordassociatie met de titel van het thema.
        - Een fiche die op elke willekeurige dag zou passen.
        - Een activiteit die het thema alleen als decor gebruikt.

        Geen enkele match is een geldig en verwacht antwoord. Veel kalenderdagen hebben niets
        met het leven in een woonzorgcentrum te maken, en een zwakke match is slechter dan geen.
        Vul de lijst niet op.

        Sorteer op sterkte, de beste match eerst. Geef per match een reden van maximaal
        tien woorden in het Nederlands, zodat een mens de koppeling kan nakijken.
        Gebruik enkel slugs die letterlijk in de catalogus staan.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'matches' => $schema->array()->required()->items($schema->object([
                'slug' => $schema->string()->required()->description('De slug van de fiche, letterlijk uit de catalogus'),
                'reason' => $schema->string()->required()->description('Waarom deze fiche bij het thema past, max 10 woorden, in het Nederlands'),
            ]))->description('Passende fiches, sterkste eerst. Leeg als geen enkele fiche past.'),
        ];
    }
}
