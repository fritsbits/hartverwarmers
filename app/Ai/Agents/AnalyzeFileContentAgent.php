<?php

namespace App\Ai\Agents;

use App\Models\Tag;
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
class AnalyzeFileContentAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $facets = config('diamant.facets');
        $facetDescriptions = collect($facets)->map(function ($facet, $slug) {
            return "- {$facet['keyword']} ({$slug}): {$facet['ik_wil']}";
        })->implode("\n");

        return <<<PROMPT
        Je bent een expert in ouderenzorg en activiteitenbegeleiding in woonzorgcentra in Vlaanderen.
        Je analyseert documenten (presentaties, PDF's, Word-bestanden) die activiteiten beschrijven voor ouderen.

        Je kent het DIAMANT-model, een pedagogisch kader met 7 doelen:
        {$facetDescriptions}

        Analyseer de aangeleverde tekst en vul de velden zo volledig mogelijk in.
        Gebruik Markdown-opmaak (lijsten, **vet**, kopjes) voor de tekstvelden preparation, inventory en process. Geen HTML.
        Schrijf altijd in het Nederlands (Belgisch/Vlaams).
        Als informatie niet in de tekst staat, laat het veld leeg.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required()->description('Korte samenvatting van de activiteit (1-2 zinnen)'),
            'preparation' => $schema->string()->description('Voorbereidingsstappen in Markdown'),
            'inventory' => $schema->string()->description('Benodigdheden als Markdown-lijst'),
            'process' => $schema->string()->description('Stap-voor-stap werkwijze in Markdown'),
            'duration_estimate' => $schema->string()->description('Geschatte duur, bijv. "30-45 minuten"'),
            'group_size_estimate' => $schema->string()->description('Geschatte groepsgrootte, bijv. "4-8 personen"'),
            'suggested_themes' => $schema->array()->items($schema->string())->description('Voorgestelde thema-slugs ('.Tag::where('type', 'theme')->pluck('slug')->implode(', ').')'),
            'suggested_goals' => $schema->array()->items($schema->string())->description('DIAMANT doel-slugs ('.collect(config('diamant.facets'))->keys()->implode(', ').')'),
            'suggested_target_audience' => $schema->array()->items($schema->string())->description('Doelgroep beschrijvingen'),
        ];
    }
}
