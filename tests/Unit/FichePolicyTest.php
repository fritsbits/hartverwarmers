<?php

namespace Tests\Unit;

use App\Models\Fiche;
use App\Models\User;
use App\Policies\FichePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FichePolicyTest extends TestCase
{
    use RefreshDatabase;

    private FichePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FichePolicy;
    }

    public function test_any_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    public function test_owner_can_update(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $fiche));
    }

    public function test_admin_can_update(): void
    {
        $admin = User::factory()->admin()->create();
        $fiche = Fiche::factory()->create();

        $this->assertTrue($this->policy->update($admin, $fiche));
    }

    public function test_curator_can_update(): void
    {
        $curator = User::factory()->curator()->create();
        $fiche = Fiche::factory()->create();

        $this->assertTrue($this->policy->update($curator, $fiche));
    }

    public function test_other_user_cannot_update(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        $this->assertFalse($this->policy->update($user, $fiche));
    }

    public function test_owner_can_delete(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $fiche));
    }

    public function test_admin_can_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $fiche = Fiche::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $fiche));
    }

    public function test_other_user_cannot_delete(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        $this->assertFalse($this->policy->delete($user, $fiche));
    }

    public function test_curator_cannot_delete(): void
    {
        $curator = User::factory()->curator()->create();
        $fiche = Fiche::factory()->create();

        $this->assertFalse($this->policy->delete($curator, $fiche));
    }

    public function test_admin_and_curator_can_toggle_diamond(): void
    {
        $admin = User::factory()->admin()->create();
        $curator = User::factory()->curator()->create();
        $user = User::factory()->create();
        $fiche = Fiche::factory()->create();

        $this->assertTrue($this->policy->toggleDiamond($admin, $fiche));
        $this->assertTrue($this->policy->toggleDiamond($curator, $fiche));
        $this->assertFalse($this->policy->toggleDiamond($user, $fiche));
    }
}
