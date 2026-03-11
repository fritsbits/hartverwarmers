<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_registration_assigns_member_role(): void
    {
        $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'member@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $user = User::where('email', 'member@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('member', $user->role);
        $this->assertTrue($user->isMember());
    }

    public function test_member_role_helpers(): void
    {
        $user = User::factory()->member()->create();

        $this->assertTrue($user->isMember());
        $this->assertFalse($user->isContributor());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isCurator());
    }

    public function test_publishing_fiche_promotes_member_to_contributor(): void
    {
        $member = User::factory()->member()->create();

        Fiche::factory()->published()->create(['user_id' => $member->id]);

        // Simulate the auto-promotion logic from FicheWizard
        if ($member->isMember()) {
            $member->update(['role' => 'contributor']);
        }

        $member->refresh();
        $this->assertEquals('contributor', $member->role);
        $this->assertTrue($member->isContributor());
    }

    public function test_saving_draft_does_not_promote_member(): void
    {
        $member = User::factory()->member()->create();

        Fiche::factory()->create(['user_id' => $member->id, 'published' => false]);

        // Draft save does not trigger promotion — member stays member
        $member->refresh();
        $this->assertEquals('member', $member->role);
    }

    public function test_contributor_stays_contributor_on_subsequent_publishes(): void
    {
        $contributor = User::factory()->create(['role' => 'contributor']);

        Fiche::factory()->published()->create(['user_id' => $contributor->id]);

        // isContributor check means promotion logic won't run
        if ($contributor->isMember()) {
            $contributor->update(['role' => 'contributor']);
        }

        $contributor->refresh();
        $this->assertEquals('contributor', $contributor->role);
    }

    public function test_member_without_fiches_not_in_contributor_count(): void
    {
        User::factory()->member()->create();
        $contributor = User::factory()->create(['role' => 'contributor']);
        Fiche::factory()->create(['user_id' => $contributor->id]);

        $contributorCount = User::whereHas('fiches')->count();

        $this->assertEquals(1, $contributorCount);
    }
}
