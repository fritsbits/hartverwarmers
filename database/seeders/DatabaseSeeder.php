<?php

namespace Database\Seeders;

use App\Models\Elaboration;
use App\Models\Initiative;
use App\Models\Organisation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create organisations
        $organisations = collect([
            Organisation::factory()->create(['name' => 'WZC Zonneveld', 'city' => 'Antwerpen']),
            Organisation::factory()->create(['name' => 'WZC De Vlinder', 'city' => 'Gent']),
            Organisation::factory()->create(['name' => 'WZC Het Anker', 'city' => 'Brugge']),
            Organisation::factory()->create(['name' => 'WZC Zonnebloem', 'city' => 'Leuven']),
            Organisation::factory()->create(['name' => 'WZC De Oase', 'city' => 'Mechelen']),
            Organisation::factory()->create(['name' => 'WZC Residentie Berchem', 'city' => 'Berchem']),
            Organisation::factory()->create(['name' => 'Hartverwarmers', 'city' => 'Antwerpen']),
        ]);

        // Create admin
        $admin = User::factory()->admin()->create([
            'name' => 'Frederik Vincx',
            'email' => 'admin@example.com',
            'password' => 'fvx853',
            'organisation_id' => $organisations->last()->id,
        ]);

        // Create named contributors
        $contributors = collect([
            User::factory()->create(['name' => 'An Peeters', 'organisation_id' => $organisations[0]->id]),
            User::factory()->create(['name' => 'Jan Verhoeven', 'organisation_id' => $organisations[1]->id]),
            User::factory()->create(['name' => 'Lisa De Graef', 'organisation_id' => $organisations[2]->id]),
            User::factory()->create(['name' => 'Katrien Willems', 'organisation_id' => $organisations[3]->id]),
            User::factory()->create(['name' => 'Thomas De Smedt', 'organisation_id' => $organisations[4]->id]),
            User::factory()->create(['name' => 'Marie Janssens', 'organisation_id' => $organisations[5]->id]),
            User::factory()->create(['name' => 'Peter Van den Berg', 'organisation_id' => $organisations[0]->id]),
            User::factory()->create(['name' => 'Sofie Claes', 'organisation_id' => $organisations[1]->id]),
        ]);

        $allUsers = $contributors->push($admin);

        // Create tags
        $interestTags = collect([
            Tag::factory()->interest()->create(['name' => 'Beheren & organiseren', 'slug' => 'beheren-organiseren']),
            Tag::factory()->interest()->create(['name' => 'Geloof & tradities', 'slug' => 'geloof-tradities']),
            Tag::factory()->interest()->create(['name' => 'Gezelschap', 'slug' => 'gezelschap']),
            Tag::factory()->interest()->create(['name' => 'Huishouden', 'slug' => 'huishouden']),
            Tag::factory()->interest()->create(['name' => 'Klussen & creatief', 'slug' => 'klussen-creatief']),
            Tag::factory()->interest()->create(['name' => 'Kunst & cultuur', 'slug' => 'kunst-cultuur']),
            Tag::factory()->interest()->create(['name' => 'Lezen & schrijven', 'slug' => 'lezen-schrijven']),
            Tag::factory()->interest()->create(['name' => 'Muziek', 'slug' => 'muziek']),
            Tag::factory()->interest()->create(['name' => 'Natuur & dieren', 'slug' => 'natuur-dieren']),
            Tag::factory()->interest()->create(['name' => 'Nieuws & actualiteit', 'slug' => 'nieuws-actualiteit']),
            Tag::factory()->interest()->create(['name' => 'Spelletjes', 'slug' => 'spelletjes']),
            Tag::factory()->interest()->create(['name' => 'Sport & Bewegen', 'slug' => 'sport-bewegen']),
            Tag::factory()->interest()->create(['name' => 'Technologie', 'slug' => 'technologie']),
            Tag::factory()->interest()->create(['name' => 'Tv & film', 'slug' => 'tv-film']),
            Tag::factory()->interest()->create(['name' => 'Uitstappen', 'slug' => 'uitstappen']),
            Tag::factory()->interest()->create(['name' => 'Zelfzorg', 'slug' => 'zelfzorg']),
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

        $allTags = $interestTags->merge($guidanceTags);

        // Create realistic initiatives with specific titles
        $initiativeTitles = [
            'Kookworkshop met bewoners',
            'Tuinproject in de binnentuin',
            'Muziekquiz jaren 60',
            'Breicafe',
            'Liefdesbrieven schrijven',
            'Valentijnsbingo',
            'Ochtendwandeling met zintuigen',
            'Vlaamse schlagernamiddag',
            'Seizoenstableau maken',
            'Verhalen van vroeger',
            'Fotoproject: ons verhaal',
            'Gedichtennamiddag over liefde',
            'Schilderen met waterverf',
            'Bloemschikken voor de leefgroep',
            'Bakken voor de buren',
        ];

        $initiatives = collect();

        foreach ($initiativeTitles as $title) {
            $initiative = Initiative::factory()->published()->create([
                'title' => $title,
                'slug' => \Str::slug($title),
                'created_by' => $allUsers->random()->id,
            ]);

            $initiative->tags()->attach($allTags->random(rand(2, 4)));
            $initiative->tags()->attach($goalTags->random(rand(1, 3)));
            $initiatives->push($initiative);
        }

        // Create elaborations for each initiative (2-5 per initiative)
        $initiatives->each(function (Initiative $initiative) use ($allUsers, $allTags) {
            $numElaborations = rand(2, 5);

            for ($i = 0; $i < $numElaborations; $i++) {
                $user = $allUsers->random();
                $elaboration = Elaboration::factory()->published()->create([
                    'initiative_id' => $initiative->id,
                    'user_id' => $user->id,
                    'has_diamond' => $i === 0,
                ]);

                $elaboration->tags()->attach($allTags->random(rand(1, 3)));
            }
        });
    }
}
