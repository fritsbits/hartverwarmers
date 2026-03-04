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
#[Model('claude-sonnet-4-6')]
class ReviewFicheAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $facets = config('diamant.facets');
        $facetDescriptions = collect($facets)->map(function ($facet, $slug) {
            return "- {$facet['keyword']} ({$slug}): {$facet['ik_wil']}";
        })->implode("\n");

        return <<<PROMPT
        Je bent een ervaren redacteur voor het Hartverwarmers-platform, een Vlaams platform voor activiteitenbegeleiders in woonzorgcentra.
        Je combineert alle verzamelde informatie tot een goed gestructureerde fiche.

        DIAMANT-doelen:
        {$facetDescriptions}

        Richtlijnen:
        - Schrijf in helder, toegankelijk Nederlands (Vlaams).
        - Gebruik Markdown-opmaak (lijsten, **vet**, kopjes) voor tekstvelden. Geen HTML.
        - De titel moet kort en uitnodigend zijn.
        - De beschrijving moet in 2-3 zinnen uitleggen wat de activiteit inhoudt.
        - Geef concrete, bruikbare kwaliteitstips (max 3).
        - Behoud de essentie van de bronbestanden.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required()->description('Uitnodigende titel voor de fiche'),
            'description' => $schema->string()->required()->description('Korte beschrijving (2-3 zinnen) in Markdown'),
            'preparation' => $schema->string()->description('Voorbereidingsstappen in Markdown'),
            'inventory' => $schema->string()->description('Benodigdheden als Markdown-lijst'),
            'process' => $schema->string()->description('Stap-voor-stap werkwijze in Markdown'),
            'materials_text' => $schema->string()->description('Kommagescheiden lijst van materialen'),
            'duration' => $schema->string()->description('Geschatte duur, bijv. "30-45 minuten"'),
            'group_size' => $schema->string()->description('Geschatte groepsgrootte, bijv. "4-8 personen"'),
            'target_audience' => $schema->array()->items($schema->string())->description('Doelgroep beschrijvingen'),
            'suggested_goals' => $schema->array()->items($schema->string())->description('DIAMANT doel-slugs ('.collect(config('diamant.facets'))->keys()->implode(', ').')'),
            'suggested_themes' => $schema->array()->items($schema->string())->description('Thema-slugs ('.Tag::where('type', 'theme')->pluck('slug')->implode(', ').')'),
            'quality_tips' => $schema->array()->items(
                $schema->object([
                    'text' => $schema->string()->required()->description('De kwaliteitstip'),
                    'section' => $schema->string()->required()->description('Het fiche-veld waar deze tip bij hoort: title, description, preparation, inventory, process, materials, duration, group_size'),
                ])
            )->description('Kwaliteitstips gekoppeld aan secties (max 3)'),
        ];
    }
}
