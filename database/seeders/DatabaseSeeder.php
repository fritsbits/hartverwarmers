<?php

namespace Database\Seeders;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create admin
        $admin = User::factory()->admin()->create([
            'first_name' => 'Frederik',
            'last_name' => 'Vincx',
            'email' => 'admin@example.com',
            'password' => 'fvx853',
            'organisation' => 'Hartverwarmers',
        ]);

        // Create named contributors
        $contributors = collect([
            User::factory()->create(['first_name' => 'An', 'last_name' => 'Peeters', 'organisation' => 'WZC Zonneveld']),
            User::factory()->create(['first_name' => 'Jan', 'last_name' => 'Verhoeven', 'organisation' => 'WZC De Vlinder']),
            User::factory()->create(['first_name' => 'Lisa', 'last_name' => 'De Graef', 'organisation' => 'WZC Het Anker']),
            User::factory()->create(['first_name' => 'Katrien', 'last_name' => 'Willems', 'organisation' => 'WZC Zonnebloem']),
            User::factory()->create(['first_name' => 'Thomas', 'last_name' => 'De Smedt', 'organisation' => 'WZC De Oase']),
            User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Janssens', 'organisation' => 'WZC Residentie Berchem']),
            User::factory()->create(['first_name' => 'Peter', 'last_name' => 'Van den Berg', 'organisation' => 'WZC Zonneveld']),
            User::factory()->create(['first_name' => 'Sofie', 'last_name' => 'Claes', 'organisation' => 'WZC De Vlinder']),
        ]);

        $allUsers = $contributors->push($admin);

        // Create tags
        $themeTags = collect([
            Tag::factory()->theme()->create(['name' => 'Beheren & organiseren', 'slug' => 'beheren-organiseren']),
            Tag::factory()->theme()->create(['name' => 'Geloof & tradities', 'slug' => 'geloof-tradities']),
            Tag::factory()->theme()->create(['name' => 'Gezelschap', 'slug' => 'gezelschap']),
            Tag::factory()->theme()->create(['name' => 'Huishouden', 'slug' => 'huishouden']),
            Tag::factory()->theme()->create(['name' => 'Klussen & creatief', 'slug' => 'klussen-creatief']),
            Tag::factory()->theme()->create(['name' => 'Kunst & cultuur', 'slug' => 'kunst-cultuur']),
            Tag::factory()->theme()->create(['name' => 'Lezen & schrijven', 'slug' => 'lezen-schrijven']),
            Tag::factory()->theme()->create(['name' => 'Muziek', 'slug' => 'muziek']),
            Tag::factory()->theme()->create(['name' => 'Natuur & dieren', 'slug' => 'natuur-dieren']),
            Tag::factory()->theme()->create(['name' => 'Nieuws & actualiteit', 'slug' => 'nieuws-actualiteit']),
            Tag::factory()->theme()->create(['name' => 'Spelletjes', 'slug' => 'spelletjes']),
            Tag::factory()->theme()->create(['name' => 'Sport & Bewegen', 'slug' => 'sport-bewegen']),
            Tag::factory()->theme()->create(['name' => 'Technologie', 'slug' => 'technologie']),
            Tag::factory()->theme()->create(['name' => 'Tv & film', 'slug' => 'tv-film']),
            Tag::factory()->theme()->create(['name' => 'Uitstappen', 'slug' => 'uitstappen']),
            Tag::factory()->theme()->create(['name' => 'Zelfzorg', 'slug' => 'zelfzorg']),
        ]);

        $guidanceTags = collect([
            Tag::factory()->guidance()->create(['name' => 'Actief zelfstandig', 'slug' => 'actief-zelfstandig']),
            Tag::factory()->guidance()->create(['name' => 'Passief begeleid', 'slug' => 'passief-begeleid']),
            Tag::factory()->guidance()->create(['name' => 'Volledig begeleid', 'slug' => 'volledig-begeleid']),
        ]);

        // Goal tags (DIAMANT model)
        $goalTags = collect([
            Tag::factory()->goal()->create(['name' => 'Doen', 'slug' => 'doel-doen']),
            Tag::factory()->goal()->create(['name' => 'Inclusief', 'slug' => 'doel-inclusief']),
            Tag::factory()->goal()->create(['name' => 'Autonomie', 'slug' => 'doel-autonomie']),
            Tag::factory()->goal()->create(['name' => 'Mensgericht', 'slug' => 'doel-mensgericht']),
            Tag::factory()->goal()->create(['name' => 'Anderen', 'slug' => 'doel-anderen']),
            Tag::factory()->goal()->create(['name' => 'Normalisatie', 'slug' => 'doel-normalisatie']),
            Tag::factory()->goal()->create(['name' => 'Talent', 'slug' => 'doel-talent']),
        ]);

        $allTags = $themeTags->merge($guidanceTags);

        // Create initiatives with real Dutch descriptions
        $initiativeData = [
            ['title' => 'Kookworkshop met bewoners', 'description' => 'Samen koken met bewoners: van recept kiezen tot aan tafel genieten. Een activiteit die zintuigen prikkelt en herinneringen oproept.'],
            ['title' => 'Tuinproject in de binnentuin', 'description' => 'Bewoners onderhouden samen een moestuin of bloementuin. Planten, zaaien en oogsten op eigen tempo.'],
            ['title' => 'Muziekquiz jaren 60', 'description' => 'Een interactieve quiz rond de grootste hits van de jaren 60. Luisteren, raden en samen meezingen.'],
            ['title' => 'Breicafe', 'description' => 'Gezellig samenkomen om te breien, haken of handwerken. Ervaren breiers helpen beginners op weg.'],
            ['title' => 'Liefdesbrieven schrijven', 'description' => 'Bewoners schrijven brieven aan geliefden, vrienden of zichzelf. Een creatieve schrijfactiviteit vol emotie en verbinding.'],
            ['title' => 'Valentijnsbingo', 'description' => 'Een feestelijke bingo rond het thema liefde en valentijn. Met leuke prijsjes en gezellige sfeer.'],
            ['title' => 'Ochtendwandeling met zintuigen', 'description' => 'Een korte wandeling waarbij bewoners bewust stilstaan bij wat ze zien, horen, ruiken en voelen.'],
            ['title' => 'Vlaamse schlagernamiddag', 'description' => 'Een namiddag vol Vlaamse schlagers en meezingers. Van Will Tura tot Helmut Lotti.'],
            ['title' => 'Seizoenstableau maken', 'description' => 'Bewoners maken samen een groot kunstwerk dat het huidige seizoen uitbeeldt. Met natuurlijke materialen en creatieve technieken.'],
            ['title' => 'Verhalen van vroeger', 'description' => 'Bewoners vertellen verhalen uit hun jeugd en delen herinneringen. Een warme activiteit die verbindt over generaties heen.'],
            ['title' => 'Fotoproject: ons verhaal', 'description' => 'Bewoners fotograferen hun dagelijks leven in het woonzorgcentrum. De mooiste foto\'s worden tentoongesteld.'],
            ['title' => 'Gedichtennamiddag over liefde', 'description' => 'Samen gedichten lezen en bespreken rond het thema liefde. Bewoners kunnen ook eigen werk voordragen.'],
            ['title' => 'Schilderen met waterverf', 'description' => 'Een creatieve sessie waarin bewoners experimenteren met waterverf. Geen ervaring nodig, wel plezier gegarandeerd.'],
            ['title' => 'Bloemschikken voor de leefgroep', 'description' => 'Bewoners maken zelf bloemstukken voor hun leefruimte. Seizoensbloemen en natuurlijke materialen staan centraal.'],
            ['title' => 'Bakken voor de buren', 'description' => 'Samen koekjes, taart of brood bakken om te delen met medebewoners en buurtgenoten. De geur alleen al maakt iedereen blij.'],
        ];

        $ficheDescriptions = [
            'Stap voor stap uitgewerkt met aandacht voor verschillende zorgniveaus.',
            'Praktisch draaiboek dat je meteen kunt gebruiken in je leefgroep.',
            'Uitwerking met focus op samenwerking en sociale interactie.',
            'Aangepaste versie voor bewoners met beperkte mobiliteit.',
            'Compacte variant die in een halfuur uit te voeren is.',
        ];

        $initiatives = collect();

        foreach ($initiativeData as $data) {
            $initiative = Initiative::factory()->published()->create([
                'title' => $data['title'],
                'slug' => \Str::slug($data['title']),
                'description' => $data['description'],
                'content' => null,
                'created_by' => $allUsers->random()->id,
            ]);

            $initiative->tags()->attach($allTags->random(rand(2, 4)));
            $initiative->tags()->attach($goalTags->random(rand(1, 3)));
            $initiatives->push($initiative);
        }

        // Create fiches for each initiative (2-5 per initiative)
        $initiatives->each(function (Initiative $initiative) use ($allUsers, $allTags, $ficheDescriptions) {
            $numFiches = rand(2, 5);

            for ($i = 0; $i < $numFiches; $i++) {
                $user = $allUsers->random();
                $suffix = $numFiches > 1 ? ' - versie '.($i + 1) : '';
                $ficheTitle = 'Uitwerking: '.$initiative->title.$suffix;

                $fiche = Fiche::factory()->published()->create([
                    'initiative_id' => $initiative->id,
                    'user_id' => $user->id,
                    'title' => $ficheTitle,
                    'slug' => \Str::slug($ficheTitle),
                    'description' => $ficheDescriptions[$i % count($ficheDescriptions)],
                    'practical_tips' => null,
                    'has_diamond' => $i === 0,
                ]);

                $fiche->tags()->attach($allTags->random(rand(1, 3)));
            }
        });
    }
}
