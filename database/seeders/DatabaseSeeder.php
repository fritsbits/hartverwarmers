<?php

namespace Database\Seeders;

use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create admin
        User::factory()->admin()->create([
            'first_name' => 'Frederik',
            'last_name' => 'Vincx',
            'email' => 'admin@example.com',
            'password' => Str::random(32),
            'organisation' => 'Hartverwarmers',
        ]);

        // Create named contributors
        User::factory()->create(['first_name' => 'An', 'last_name' => 'Peeters', 'organisation' => 'WZC Zonneveld']);
        User::factory()->create(['first_name' => 'Jan', 'last_name' => 'Verhoeven', 'organisation' => 'WZC De Vlinder']);
        User::factory()->create(['first_name' => 'Lisa', 'last_name' => 'De Graef', 'organisation' => 'WZC Het Anker']);
        User::factory()->create(['first_name' => 'Katrien', 'last_name' => 'Willems', 'organisation' => 'WZC Zonnebloem']);
        User::factory()->create(['first_name' => 'Thomas', 'last_name' => 'De Smedt', 'organisation' => 'WZC De Oase']);
        User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Janssens', 'organisation' => 'WZC Residentie Berchem']);
        User::factory()->create(['first_name' => 'Peter', 'last_name' => 'Van den Berg', 'organisation' => 'WZC Zonneveld']);
        User::factory()->create(['first_name' => 'Sofie', 'last_name' => 'Claes', 'organisation' => 'WZC De Vlinder']);

        // Create theme tags
        Tag::factory()->theme()->create(['name' => 'Beheren & organiseren', 'slug' => 'beheren-organiseren']);
        Tag::factory()->theme()->create(['name' => 'Geloof & tradities', 'slug' => 'geloof-tradities']);
        Tag::factory()->theme()->create(['name' => 'Gezelschap', 'slug' => 'gezelschap']);
        Tag::factory()->theme()->create(['name' => 'Huishouden', 'slug' => 'huishouden']);
        Tag::factory()->theme()->create(['name' => 'Klussen & creatief', 'slug' => 'klussen-creatief']);
        Tag::factory()->theme()->create(['name' => 'Kunst & cultuur', 'slug' => 'kunst-cultuur']);
        Tag::factory()->theme()->create(['name' => 'Lezen & schrijven', 'slug' => 'lezen-schrijven']);
        Tag::factory()->theme()->create(['name' => 'Muziek', 'slug' => 'muziek']);
        Tag::factory()->theme()->create(['name' => 'Natuur & dieren', 'slug' => 'natuur-dieren']);
        Tag::factory()->theme()->create(['name' => 'Nieuws & actualiteit', 'slug' => 'nieuws-actualiteit']);
        Tag::factory()->theme()->create(['name' => 'Spelletjes', 'slug' => 'spelletjes']);
        Tag::factory()->theme()->create(['name' => 'Sport & Bewegen', 'slug' => 'sport-bewegen']);
        Tag::factory()->theme()->create(['name' => 'Technologie', 'slug' => 'technologie']);
        Tag::factory()->theme()->create(['name' => 'Tv & film', 'slug' => 'tv-film']);
        Tag::factory()->theme()->create(['name' => 'Uitstappen', 'slug' => 'uitstappen']);
        Tag::factory()->theme()->create(['name' => 'Zelfzorg', 'slug' => 'zelfzorg']);

        // Create guidance tags
        Tag::factory()->guidance()->create(['name' => 'Actief zelfstandig', 'slug' => 'actief-zelfstandig']);
        Tag::factory()->guidance()->create(['name' => 'Passief begeleid', 'slug' => 'passief-begeleid']);
        Tag::factory()->guidance()->create(['name' => 'Volledig begeleid', 'slug' => 'volledig-begeleid']);

        // Goal tags (DIAMANT model)
        Tag::factory()->goal()->create(['name' => 'Doen', 'slug' => 'doel-doen']);
        Tag::factory()->goal()->create(['name' => 'Inclusief', 'slug' => 'doel-inclusief']);
        Tag::factory()->goal()->create(['name' => 'Autonomie', 'slug' => 'doel-autonomie']);
        Tag::factory()->goal()->create(['name' => 'Mensgericht', 'slug' => 'doel-mensgericht']);
        Tag::factory()->goal()->create(['name' => 'Anderen', 'slug' => 'doel-anderen']);
        Tag::factory()->goal()->create(['name' => 'Normalisatie', 'slug' => 'doel-normalisatie']);
        Tag::factory()->goal()->create(['name' => 'Talent', 'slug' => 'doel-talent']);

        // Set initiative images for slugs that have processed WebP files
        $initiativeImages = [
            'beweging-fit', 'bingo', 'bordspellen', 'brein-fit', 'creatief-atelier',
            'dieren-ervaren', 'feesten-vieren', 'film-theater', 'fotos-herinneringen',
            'geloof-spiritualiteit', 'gesprekken-voeren', 'handwerken', 'herinneringen-delen',
            'kaartspellen', 'muziek-maken', 'natuur-ervaren', 'quiz', 'raadspellen',
            'samen-koken', 'tekenen-schilderen', 'tuinieren', 'uitstappen', 'voorlezen',
            'woord-taalspellen', 'zorg-verzorging', 'teamondersteuning',
        ];

        foreach ($initiativeImages as $slug) {
            Initiative::where('slug', $slug)
                ->update(['image' => "/img/initiatives/{$slug}.webp"]);
        }
    }
}
