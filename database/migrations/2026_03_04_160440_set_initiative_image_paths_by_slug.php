<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $slugs = [
            'beweging-fit', 'bingo', 'bordspellen', 'brein-fit', 'creatief-atelier',
            'dieren-ervaren', 'feesten-vieren', 'film-theater', 'fotos-herinneringen',
            'geloof-spiritualiteit', 'gesprekken-voeren', 'handwerken', 'herinneringen-delen',
            'kaartspellen', 'muziek-maken', 'natuur-ervaren', 'quiz', 'raadspellen',
            'samen-koken', 'tekenen-schilderen', 'tuinieren', 'uitstappen', 'voorlezen',
            'woord-taalspellen', 'zorg-verzorging', 'teamondersteuning',
        ];

        foreach ($slugs as $slug) {
            DB::table('initiatives')
                ->where('slug', $slug)
                ->update(['image' => "/img/initiatives/{$slug}.webp"]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'beweging-fit', 'bingo', 'bordspellen', 'brein-fit', 'creatief-atelier',
            'dieren-ervaren', 'feesten-vieren', 'film-theater', 'fotos-herinneringen',
            'geloof-spiritualiteit', 'gesprekken-voeren', 'handwerken', 'herinneringen-delen',
            'kaartspellen', 'muziek-maken', 'natuur-ervaren', 'quiz', 'raadspellen',
            'samen-koken', 'tekenen-schilderen', 'tuinieren', 'uitstappen', 'voorlezen',
            'woord-taalspellen', 'zorg-verzorging', 'teamondersteuning',
        ];

        foreach ($slugs as $slug) {
            DB::table('initiatives')
                ->where('slug', $slug)
                ->update(['image' => null]);
        }
    }
};
