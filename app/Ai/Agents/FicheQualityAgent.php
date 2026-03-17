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
#[Model('claude-sonnet-4-6')]
class FicheQualityAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $facets = config('diamant.facets');
        $facetDescriptions = collect($facets)->map(function ($facet, $slug) {
            return "- {$facet['keyword']} ({$slug}): {$facet['ik_wil']}";
        })->implode("\n");

        return <<<PROMPT
        Je bent een kwaliteitsbeoordelaar voor het Hartverwarmers-platform, een Vlaams platform voor activiteitenbegeleiders in woonzorgcentra.

        Beoordeel de gegeven activiteitenfiche op twee dimensies:

        1. **DIAMANT-aansluiting**: Hoe goed sluit de activiteit aan bij het DIAMANT-model? Worden de doelen expliciet of impliciet aangesproken? Is er diepgang of is het oppervlakkig?

        DIAMANT-doelen:
        {$facetDescriptions}

        2. **Originaliteit**: Hoe creatief en vernieuwend is de activiteit? Is het een standaard bingo/quiz of biedt het een frisse invalshoek die animatoren inspireert?

        Geef een score van 0-100 waarbij:
        - 0-30: Zwak — weinig DIAMANT-aansluiting, standaard activiteit zonder diepgang
        - 31-60: Redelijk — enige aansluiting, maar weinig origineel of oppervlakkig uitgewerkt
        - 61-80: Goed — duidelijke DIAMANT-aansluiting, interessante invalshoek
        - 81-100: Uitstekend — sterke aansluiting bij meerdere doelen, origineel en inspirerend

        Schrijf de motivatie in 2-3 korte zinnen in het Nederlands. Benoem welke DIAMANT-doelen aangesproken worden en wat de activiteit bijzonder maakt (of niet).
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'score' => $schema->integer()->required()->description('Kwaliteitsscore van 0 tot 100'),
            'justification' => $schema->string()->required()->description('Korte motivatie in het Nederlands (2-3 zinnen)'),
        ];
    }
}
