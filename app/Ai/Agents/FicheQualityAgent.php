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

        Beoordeel de gegeven activiteitenfiche op twee dimensies. Geef voor elke dimensie een score van 0-100 en een korte motivatie in het Nederlands (2-3 zinnen).

        ## Dimensie 1: Inhoudelijke kwaliteit (quality_score)

        Beoordeel de activiteit zelf — los van hoe goed de fiche is ingevuld.

        **DIAMANT-aansluiting**: Hoe goed sluit de activiteit aan bij het DIAMANT-model? Worden de doelen expliciet of impliciet aangesproken? Is er diepgang of is het oppervlakkig?

        DIAMANT-doelen:
        {$facetDescriptions}

        **Originaliteit**: Hoe creatief en vernieuwend is de activiteit? Is het een standaard bingo/quiz of biedt het een frisse invalshoek die animatoren inspireert?

        **Betekenis**: Gaat het verder dan "bewoners bezighouden"? Draagt het bij aan eigenwaarde, verbondenheid of zingeving? Kunnen bewoners regie nemen en eigen keuzes maken?

        Scoring:
        - 0-30: Zwak — weinig DIAMANT-aansluiting, standaard activiteit zonder diepgang
        - 31-60: Redelijk — enige aansluiting, maar weinig origineel of oppervlakkig
        - 61-80: Goed — duidelijke DIAMANT-aansluiting, interessante invalshoek
        - 81-100: Uitstekend — sterke aansluiting bij meerdere doelen, origineel en inspirerend

        ## Dimensie 2: Presentatiekwaliteit (presentation_score)

        Beoordeel hoe goed de fiche is ingevuld — los van de activiteit zelf.

        **Titel**: Is de titel specifiek en onderscheidend? Of te generiek ("Memory", "Bingo", "Quiz")? Kan een collega uit de titel afleiden wat de activiteit inhoudt?

        **Omschrijving**: Geeft de omschrijving een duidelijk beeld? Vertelt het WAT de activiteit is, VOOR WIE, HOE het globaal verloopt, en wat het OPLEVERT? Of is het te kort/vaag?

        **Uitvoerbaarheid**: Kan een collega deze activiteit uitvoeren zonder extra informatie op te zoeken? Zijn er concrete stappen, timing, voorbeeldvragen? Of staat er alleen "zie bijlage" of "speel het zoals normaal"?

        **Volledigheid**: Zijn de velden werkwijze, voorbereiding en benodigdheden zinvol ingevuld? Niet alleen aanwezig, maar bruikbaar?

        Scoring:
        - 0-30: Zwak — generieke titel, nauwelijks omschrijving, niet uitvoerbaar zonder extra info
        - 31-60: Redelijk — basisinfo aanwezig maar onvolledig of vaag
        - 61-80: Goed — duidelijke omschrijving, concrete stappen, goed bruikbaar
        - 81-100: Uitstekend — specifieke titel, complete omschrijving, direct uitvoerbaar, alle velden goed gevuld
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'quality_score' => $schema->integer()->required()->description('Inhoudelijke kwaliteitsscore van 0 tot 100'),
            'quality_justification' => $schema->string()->required()->description('Korte motivatie inhoudelijke kwaliteit in het Nederlands (2-3 zinnen)'),
            'presentation_score' => $schema->integer()->required()->description('Presentatiekwaliteitsscore van 0 tot 100'),
            'presentation_justification' => $schema->string()->required()->description('Korte motivatie presentatiekwaliteit in het Nederlands (2-3 zinnen)'),
        ];
    }
}
