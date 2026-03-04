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
        $seededTitles = [
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

        $initiativeIds = DB::table('initiatives')
            ->whereIn('title', $seededTitles)
            ->pluck('id');

        if ($initiativeIds->isEmpty()) {
            return;
        }

        $ficheIds = DB::table('fiches')
            ->whereIn('initiative_id', $initiativeIds)
            ->pluck('id');

        // Delete taggables for fiches
        if ($ficheIds->isNotEmpty()) {
            DB::table('taggables')
                ->where('taggable_type', 'App\\Models\\Fiche')
                ->whereIn('taggable_id', $ficheIds)
                ->delete();

            // Delete likes on fiches
            DB::table('likes')
                ->where('likeable_type', 'App\\Models\\Fiche')
                ->whereIn('likeable_id', $ficheIds)
                ->delete();

            // Delete comments on fiches
            DB::table('comments')
                ->where('commentable_type', 'App\\Models\\Fiche')
                ->whereIn('commentable_id', $ficheIds)
                ->delete();
        }

        // Delete taggables for initiatives
        DB::table('taggables')
            ->where('taggable_type', 'App\\Models\\Initiative')
            ->whereIn('taggable_id', $initiativeIds)
            ->delete();

        // Delete likes on initiatives
        DB::table('likes')
            ->where('likeable_type', 'App\\Models\\Initiative')
            ->whereIn('likeable_id', $initiativeIds)
            ->delete();

        // Delete comments on initiatives
        DB::table('comments')
            ->where('commentable_type', 'App\\Models\\Initiative')
            ->whereIn('commentable_id', $initiativeIds)
            ->delete();

        // Delete fiches (force-delete, bypassing soft deletes)
        DB::table('fiches')
            ->whereIn('initiative_id', $initiativeIds)
            ->delete();

        // Delete initiatives (force-delete, bypassing soft deletes)
        DB::table('initiatives')
            ->whereIn('id', $initiativeIds)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Seeded demo data cannot be restored
    }
};
