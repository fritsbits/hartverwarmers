<?php

namespace Database\Seeders;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Illuminate\Database\Seeder;

class OkrSeeder extends Seeder
{
    public function run(): void
    {
        $objectives = [
            ['slug' => 'presentatiekwaliteit', 'title' => 'Fichekwaliteit', 'position' => 1],
            ['slug' => 'onboarding', 'title' => 'Activatie', 'position' => 2],
            ['slug' => 'bedankjes', 'title' => 'Interactie', 'position' => 3],
            ['slug' => 'nieuwsbrief', 'title' => 'Retentie', 'position' => 4],
        ];

        foreach ($objectives as $data) {
            Objective::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $keyResults = [
            'presentatiekwaliteit' => [
                ['label' => 'Gemiddelde presentatiescore', 'metric_key' => 'presentation_score_avg', 'target_unit' => ''],
            ],
            'onboarding' => [
                ['label' => 'Aanmeldingen', 'metric_key' => 'onboarding_signup_count', 'target_unit' => ''],
                ['label' => 'E-mailverificatie', 'metric_key' => 'onboarding_verification_rate', 'target_unit' => '%'],
                ['label' => 'Return visit binnen 7 dagen', 'metric_key' => 'onboarding_return_7d_rate', 'target_unit' => '%'],
                ['label' => 'Interactie binnen 30 dagen', 'metric_key' => 'onboarding_interaction_30d_rate', 'target_unit' => '%'],
                ['label' => 'Follow-up reactie na download', 'metric_key' => 'onboarding_followup_response_rate', 'target_unit' => '%'],
            ],
            'bedankjes' => [
                ['label' => 'Bedankratio', 'metric_key' => 'thank_rate', 'target_unit' => '%'],
            ],
            'nieuwsbrief' => [
                ['label' => 'Activatie na nieuwsbrief', 'metric_key' => 'newsletter_activation_rate', 'target_unit' => '%'],
            ],
        ];

        foreach ($keyResults as $objectiveSlug => $list) {
            $objective = Objective::where('slug', $objectiveSlug)->firstOrFail();
            foreach ($list as $i => $kr) {
                KeyResult::updateOrCreate(
                    ['objective_id' => $objective->id, 'metric_key' => $kr['metric_key']],
                    [...$kr, 'objective_id' => $objective->id, 'position' => $i + 1],
                );
            }
        }

        $initiatives = [
            'presentatiekwaliteit' => [
                ['slug' => 'ai-suggesties', 'label' => 'AI-suggesties'],
            ],
            'onboarding' => [
                ['slug' => 'onboarding-emails', 'label' => 'Onboarding-e-mails'],
            ],
            'bedankjes' => [
                ['slug' => 'bedankflow-na-download', 'label' => 'Bedankflow na download', 'started_at' => '2026-05-11'],
            ],
            'nieuwsbrief' => [
                ['slug' => 'nieuwsbrief-systeem', 'label' => 'Nieuwsbrief-systeem'],
            ],
        ];

        foreach ($initiatives as $objectiveSlug => $list) {
            $objective = Objective::where('slug', $objectiveSlug)->firstOrFail();
            foreach ($list as $i => $init) {
                Initiative::updateOrCreate(
                    ['objective_id' => $objective->id, 'slug' => $init['slug']],
                    [...$init, 'objective_id' => $objective->id, 'position' => $i + 1],
                );
            }
        }
    }
}
