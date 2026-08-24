<?php

use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Illuminate\Database\Migrations\Migration;

/**
 * Ships the "Inhoudelijke kwaliteit" OKR as data, so a deploy brings it live
 * without a manual seeder run. Idempotent: re-running only rewrites the same
 * values.
 */
return new class extends Migration
{
    /** Objective slug => position, after inserting the new objective at 2. */
    private const POSITIONS = [
        'presentatiekwaliteit' => 1,
        'onboarding' => 3,
        'bedankjes' => 4,
        'nieuwsbrief' => 5,
        'reactivatie' => 6,
    ];

    public function up(): void
    {
        // Only brings existing installs forward. A database without objectives
        // is a fresh one — OkrSeeder populates those — and seeding rows here
        // would land in every test database too.
        if (Objective::count() === 0) {
            return;
        }

        // "Fichekwaliteit" measured presentation only; with a second quality
        // objective beside it the old title no longer says which one it is.
        Objective::where('slug', 'presentatiekwaliteit')->update(['title' => 'Presentatiekwaliteit']);

        foreach (self::POSITIONS as $slug => $position) {
            Objective::where('slug', $slug)->update(['position' => $position]);
        }

        $objective = Objective::updateOrCreate(
            ['slug' => 'inhoudelijke-kwaliteit'],
            [
                'title' => 'Inhoudelijke kwaliteit',
                'description' => 'Hoe sterk staan de fiches inhoudelijk: aansluiting bij DIAMANT, originaliteit en betekenis voor bewoners.',
                'status' => 'on_track',
                'position' => 2,
            ],
        );

        KeyResult::updateOrCreate(
            ['objective_id' => $objective->id, 'metric_key' => 'diamant_score_share'],
            [
                'label' => 'Fiches met sterke diamantscore',
                'target_value' => 35,
                'target_unit' => '%',
                'position' => 1,
            ],
        );
    }

    public function down(): void
    {
        $objective = Objective::where('slug', 'inhoudelijke-kwaliteit')->first();

        if ($objective !== null) {
            KeyResult::where('objective_id', $objective->id)->delete();
            $objective->delete();
        }

        Objective::where('slug', 'presentatiekwaliteit')->update(['title' => 'Fichekwaliteit']);

        foreach (['presentatiekwaliteit' => 1, 'onboarding' => 2, 'bedankjes' => 3, 'nieuwsbrief' => 4, 'reactivatie' => 5] as $slug => $position) {
            Objective::where('slug', $slug)->update(['position' => $position]);
        }
    }
};
