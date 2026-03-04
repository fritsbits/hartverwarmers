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
class MatchInitiativeAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Je bent een expert in het koppelen van praktische uitwerkingen (fiches) aan initiatieven in de ouderenzorg.
        Je krijgt een titel, beschrijving en samenvatting van een fiche, plus een lijst van beschikbare initiatieven.

        Selecteer de top 3 initiatieven die het beste passen bij de fiche.
        Geef voor elk initiatief een korte reden (1 zin, in het Nederlands) waarom het past.
        Sorteer op relevantie (meest relevant eerst).
        Als er minder dan 3 goede matches zijn, geef alleen de goede matches.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'matched_initiative_ids' => $schema->array()->items($schema->integer())->required()->description('Top 3 initiatief-IDs, gesorteerd op relevantie'),
            'match_reasons' => $schema->array()->items($schema->string())->required()->description('Reden per match, in het Nederlands'),
        ];
    }
}
