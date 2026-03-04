<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Tag;
use App\Models\User;
use Illuminate\View\View;

class DesignSystemController extends Controller
{
    public function index(): View
    {
        $goalTags = collect(config('diamant.facets'))->map(fn ($facet, $slug) => new Tag([
            'name' => $facet['keyword'],
            'slug' => 'doel-'.$slug,
            'type' => 'goal',
        ]));

        $themeTags = collect([
            ['name' => 'Muziek', 'slug' => 'muziek', 'type' => 'theme'],
            ['name' => 'Bewegen', 'slug' => 'bewegen', 'type' => 'theme'],
            ['name' => 'Natuur', 'slug' => 'natuur', 'type' => 'theme'],
        ])->map(fn ($data) => new Tag($data));

        $user = new User([
            'first_name' => 'Maria',
            'last_name' => 'Janssen',
            'email' => 'maria@voorbeeld.nl',
            'role' => 'contributor',
            'organisation' => 'Woonzorgcentrum De Zonneweide',
            'function_title' => 'Activiteitenbegeleider',
        ]);
        $user->id = 1;

        $users = collect([
            $user,
            tap(new User(['first_name' => 'Pieter', 'last_name' => 'De Vries']), fn ($u) => $u->id = 2),
            tap(new User(['first_name' => 'Anne', 'last_name' => 'Bakker']), fn ($u) => $u->id = 3),
            tap(new User(['first_name' => 'Jan', 'last_name' => 'Smit']), fn ($u) => $u->id = 4),
            tap(new User(['first_name' => 'Els', 'last_name' => 'Peters']), fn ($u) => $u->id = 5),
            tap(new User(['first_name' => 'Tom', 'last_name' => 'Hendriks']), fn ($u) => $u->id = 6),
        ]);

        $initiative = new Initiative([
            'title' => 'Muziekbingo voor bewoners',
            'slug' => 'muziekbingo-voor-bewoners',
            'description' => 'Een gezellige muziekbingo die bewoners samenbrengt en herinneringen oproept via bekende liedjes uit hun jeugd.',
            'published' => true,
        ]);
        $initiative->id = 1;
        $initiative->fiches_count = 3;
        $initiative->setRelation('tags', $themeTags);

        $fiche = new Fiche([
            'title' => 'Muziekbingo met jaren 60 hits',
            'slug' => 'muziekbingo-met-jaren-60-hits',
            'description' => 'Praktische uitwerking van een muziekbingo met herkenbare nummers uit de jaren 60.',
            'published' => true,
            'has_diamond' => true,
        ]);
        $fiche->id = 1;
        $fiche->setRelation('user', $user);
        $fiche->setRelation('initiative', $initiative);
        $fiche->setRelation('tags', $themeTags->take(2));

        return view('admin.design-system', [
            'goalTags' => $goalTags,
            'themeTags' => $themeTags,
            'user' => $user,
            'users' => $users,
            'initiative' => $initiative,
            'fiche' => $fiche,
            'facets' => config('diamant.facets'),
        ]);
    }
}
