<?php

namespace Tests\Feature\Okr;

use App\Models\Okr\Initiative;
use App\Models\Okr\KeyResult;
use App\Models\Okr\Objective;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObjectiveModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_objective_has_ordered_key_results_and_initiatives(): void
    {
        $objective = Objective::factory()->create();

        KeyResult::factory()->for($objective)->create(['position' => 2, 'label' => 'second']);
        KeyResult::factory()->for($objective)->create(['position' => 1, 'label' => 'first']);

        Initiative::factory()->for($objective)->create(['position' => 2, 'slug' => 'b', 'label' => 'B']);
        Initiative::factory()->for($objective)->create(['position' => 1, 'slug' => 'a', 'label' => 'A']);

        $this->assertSame(['first', 'second'], $objective->keyResults->pluck('label')->all());
        $this->assertSame(['A', 'B'], $objective->initiatives->pluck('label')->all());
    }

    public function test_deleting_objective_cascades_to_key_results_and_initiatives(): void
    {
        $objective = Objective::factory()->create();
        KeyResult::factory()->for($objective)->create();
        Initiative::factory()->for($objective)->create();

        $objective->delete();

        $this->assertSame(0, KeyResult::count());
        $this->assertSame(0, Initiative::count());
    }
}
