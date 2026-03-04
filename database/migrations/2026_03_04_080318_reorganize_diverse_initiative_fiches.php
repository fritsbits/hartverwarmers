<?php

use App\Models\Initiative;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reorganizes fiches from the catch-all "Diverse" initiative into
     * thematically appropriate initiatives, creates "Teamondersteuning"
     * for staff-support fiches, and soft-deletes "Diverse".
     */
    public function up(): void
    {
        $diverse = Initiative::where('slug', 'diverse')->first();

        if (! $diverse) {
            return;
        }

        // Create the new "Teamondersteuning" initiative
        $teamondersteuning = Initiative::create([
            'title' => 'Teamondersteuning',
            'slug' => 'teamondersteuning',
            'description' => 'Tips en ideeën om het welzijn van medewerkers te ondersteunen.',
            'published' => true,
            'created_by' => $diverse->created_by,
        ]);

        // Look up target initiatives by slug
        $targets = Initiative::whereIn('slug', [
            'raadspellen', 'bordspellen', 'herinneringen-delen',
            'fotos-herinneringen', 'samen-koken', 'zorg-verzorging',
            'gesprekken-voeren', 'beweging-fit',
        ])->pluck('id', 'slug');

        // === Cluster 1: Team support → Teamondersteuning ===
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [590, 578, 587, 586, 577, 581, 575, 588, 591])
            ->update(['initiative_id' => $teamondersteuning->id]);

        // === Cluster 2: Mantelzorg → Zorg & verzorging ===
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [571, 582, 584])
            ->update(['initiative_id' => $targets['zorg-verzorging']]);

        // === Cluster 3: Move to existing initiatives ===

        // Spelletjes → Raadspellen
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [569, 568])
            ->update(['initiative_id' => $targets['raadspellen']]);

        // Spelnamiddag → Bordspellen
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [567])
            ->update(['initiative_id' => $targets['bordspellen']]);

        // Levensverhalen & koffer → Herinneringen delen
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [570, 574])
            ->update(['initiative_id' => $targets['herinneringen-delen']]);

        // Virtuele rondleiding legerdienst → Herinneringen delen
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [576])
            ->update(['initiative_id' => $targets['herinneringen-delen']]);

        // High tea → Samen koken
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [572])
            ->update(['initiative_id' => $targets['samen-koken']]);

        // Handen wassen → Zorg & verzorging
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [589])
            ->update(['initiative_id' => $targets['zorg-verzorging']]);

        // Zilverwijzer → Gesprekken voeren
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [573])
            ->update(['initiative_id' => $targets['gesprekken-voeren']]);

        // Carwash → Beweging & fit
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [579])
            ->update(['initiative_id' => $targets['beweging-fit']]);

        // === Remaining 3 fiches ===

        // Activiteiten aan het raam → Gesprekken voeren
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [585])
            ->update(['initiative_id' => $targets['gesprekken-voeren']]);

        // #hallovanhier social media → Foto's & herinneringen
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [583])
            ->update(['initiative_id' => $targets['fotos-herinneringen']]);

        // Verrassingspakket → Zorg & verzorging
        DB::table('fiches')
            ->where('initiative_id', $diverse->id)
            ->whereIn('id', [580])
            ->update(['initiative_id' => $targets['zorg-verzorging']]);

        // Soft-delete "Diverse" (should have 0 fiches left)
        $diverse->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore "Diverse"
        $diverse = Initiative::withTrashed()->where('slug', 'diverse')->first();

        if ($diverse) {
            $diverse->restore();
        }

        // Remove "Teamondersteuning" and reassign its fiches back to Diverse
        $teamondersteuning = Initiative::where('slug', 'teamondersteuning')->first();

        if ($teamondersteuning && $diverse) {
            DB::table('fiches')
                ->where('initiative_id', $teamondersteuning->id)
                ->update(['initiative_id' => $diverse->id]);

            $teamondersteuning->forceDelete();
        }

        // Note: moving individual fiches back from other initiatives to Diverse
        // is not automated in rollback — the fiche IDs would need manual mapping.
    }
};
