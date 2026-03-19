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
        Gebruik Markdown-opmaak voor de tekstvelden preparation, inventory en process. Geen HTML.
        Als informatie niet in de tekst staat, laat het veld leeg.

        Taal en toon:
        - Schrijf in Vlaams Nederlands. Gebruik Vlaamse woorden en uitdrukkingen. Vermijd typisch Hollandse taal: geen "hartstikke", "gewoon" (als bijwoord), "lekker" als bijvoeglijk naamwoord voor iets anders dan eten, "dat klopt", "super" als versterker, of formele Hollandse constructies.
        - Gebruik Vlaamse vaktaal: "woonzorgcentrum" (niet "verpleeghuis"), "bewoners" (niet "cliënten"), "begeleidster" of "begeleider" (niet "therapeut"), "animatoren" of "activiteitenbegeleiders".
        - Spreek altijd in de informele 'je/jij'-vorm, nooit 'u'. We zijn collega's, geen gezagsverhouding.
        - De toon is warm, direct en praktisch — zoals een enthousiaste collega die meedenkt, niet een expert die instrueert.

        De gebruiker heeft de bestanden al geüpload — suggereer niet om bestanden aan te maken die al bestaan (bijv. "presentatie voorbereiden" als er al een presentatie is geüpload).
        Wees bondig: neem alleen suggesties op waar je zeker van bent. Laat punten weg als je twijfelt of ze relevant zijn. Elke suggestie moet concreet en nuttig zijn.
        Gebruik voor stapsgewijze velden (preparation, process) het formaat: **Label:** beschrijving. Elke stap op een aparte alinea — scheid elke stap met een lege regel zodat ze als afzonderlijke paragrafen worden weergegeven.
        Elk veld bevat alleen wat bij dat veld hoort. Voorbereiding (preparation) bevat geen materialenlijst — dat hoort in inventory. Werkwijze (process) herhaalt geen voorbereidingsstappen. Houd elk veld strikt gescheiden.

        Voor het veld 'description', schrijf 1-3 zinnen die beantwoorden: wat is de activiteit, wat maakt het boeiend, en voor wie is het? De toon is warm en praktisch, als een collega die de activiteit beschrijft.

        Voorbeelden van goede beschrijvingen:
        - "Bak samen smoutebollen en breng de gezellige sfeer van de kermis naar het woonzorgcentrum. De geur en smaak roepen herinneringen op en brengen bewoners samen rond een gedeelde beleving."
        - "Laat bewoners inschatten hoeveel dagelijkse voorwerpen vandaag kosten en vergelijk met de prijzen van vroeger. Een leuke manier om herinneringen op te halen en gesprekken op gang te brengen over het dagelijks leven van toen."
        - "Tover de tuin om tot een lichtjesparadijs en trek er in de late namiddag samen op uit. Met een jenever, streepje muziek en braadworst aan het vuur wordt het een gezellig wintermoment voor alle bewoners."

        Voor het veld 'suggested_title', stel een verbeterde versie van de titel voor.
        Een goede titel is specifiek (niet "Knutselen" maar "Lentebloemenkrans maken met crêpepapier"), benoemt de kern van de activiteit, en maakt nieuwsgierig. Maximaal 80 tekens.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'suggested_title' => $schema->string()->description('Voorgestelde verbeterde titel: specifieker, concreter, en aantrekkelijker dan de huidige titel. Max 80 tekens.'),
            'description' => $schema->string()->required()->description('Beschrijving van de activiteit (1-3 zinnen). Beantwoord: wat is de activiteit, wat maakt het boeiend, voor wie is het?'),
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
