<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Correct the fiche redistribution from "Diverse".
     *
     * Handles two scenarios:
     * - Production: the old 2026_03_04 migration already ran with different mappings
     *   and created a "Teamondersteuning" initiative. This corrects those mappings.
     * - Fresh/local: fiches may still be in Diverse. Moves them to correct destinations.
     *
     * Uses slugs so it works regardless of auto-increment IDs.
     */
    public function up(): void
    {
        $targetIds = DB::table('initiatives')
            ->whereIn('slug', [
                'zorg-verzorging', 'herinneringen-delen', 'fotos-herinneringen',
                'feesten-vieren', 'creatief-atelier', 'bordspellen', 'raadspellen',
            ])
            ->pluck('id', 'slug');

        // Move fiches by slug to correct initiatives, regardless of current location.
        $moves = [
            'zorg-verzorging' => [
                'bedank-de-medewerkers',
                'bespreek-mentale-veerkracht-met-je-team',
                'bied-meeneemmaaltijden-aan-voor-medewerkers',
                'boodschappen-bestellen-op-het-werk',
                'doe-cursus-handen-wassen',
                'doe-een-snelcursus-psychosociale-opvang',
                'fris-verpleegkundige-kennis-op',
                'gebruik-zilverwijzer-om-veerkracht-en-zelfmanagement-van-bewoners-te-versterken',
                'geef-mantelzorgers-tips-om-de-zorg-vol-te-houden',
                'geef-zelfzorgopdrachten-aan-mantelzorgers',
                'nodig-een-vertrouwenspersoon-uit-voor-medewerkers',
                'maak-een-apart-email-adres-voor-boodschappen-aan-bewoners',
                'maak-een-overzicht-van-alle-steunbetuigingen',
                'stem-de-zorg-af-tussen-bewoner-mantelzorger-en-het-woonzorgcentrum',
            ],
            'herinneringen-delen' => [
                'de-koffer-van-je-leven',
                'verzamel-levensverhalen-met-families',
            ],
            'fotos-herinneringen' => [
                'geef-virtuele-rondleiding-legerdienst',
            ],
            'feesten-vieren' => [
                'high-tea-afternoon',
                'stel-een-verrassingspakket-samen',
            ],
            'creatief-atelier' => [
                'inspireer-activiteiten-aan-het-raam',
                'neem-deel-aan-hallovanhier-uitdagingen-op-sociale-media',
            ],
            'bordspellen' => [
                'spelnamiddag-ik-zet-deze-er-nogmaals-op-omdat-men-deze-niet-kon-downloaden-hopelijk-nu-dan-meer-succes',
                'visspel',
            ],
            'raadspellen' => [
                'wat-is-er-fout',
            ],
        ];

        foreach ($moves as $initiativeSlug => $ficheSlugs) {
            if (isset($targetIds[$initiativeSlug])) {
                DB::table('fiches')
                    ->whereIn('slug', $ficheSlugs)
                    ->update(['initiative_id' => $targetIds[$initiativeSlug]]);
            }
        }

        // If the old migration created "Teamondersteuning", move any remaining
        // fiches there to Zorg & verzorging and delete the initiative.
        $teamondersteuning = DB::table('initiatives')
            ->where('slug', 'teamondersteuning')
            ->first();

        if ($teamondersteuning && isset($targetIds['zorg-verzorging'])) {
            DB::table('fiches')
                ->where('initiative_id', $teamondersteuning->id)
                ->update(['initiative_id' => $targetIds['zorg-verzorging']]);

            DB::table('initiatives')
                ->where('id', $teamondersteuning->id)
                ->delete();
        }

        // Soft-delete "Organiseer een carwash"
        DB::table('fiches')
            ->where('slug', 'organiseer-een-carwash')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // Soft-delete the Diverse initiative if not already deleted
        DB::table('initiatives')
            ->where('slug', 'diverse')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
    }

    /**
     * This migration cannot be fully reversed as it corrects a previous migration.
     */
    public function down(): void
    {
        // Restore the carwash fiche
        DB::table('fiches')
            ->where('slug', 'organiseer-een-carwash')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);
    }
};
