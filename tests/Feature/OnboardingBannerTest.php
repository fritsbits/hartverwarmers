<?php

namespace Tests\Feature;

use App\Livewire\OnboardingBanner;
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_no_banner(): void
    {
        Livewire::test(OnboardingBanner::class)
            ->assertSet('level', null)
            ->assertDontSee('Dit kan je nu allemaal')
            ->assertDontSee('eerste fiche gedeeld');
    }

    public function test_new_user_sees_level_1_banner(): void
    {
        $user = User::factory()->member()->create(['onboarded_at' => null, 'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', 1)
            ->assertSee('Dit kan je nu allemaal')
            ->assertSee('Dit kan je nu allemaal');
    }

    public function test_dismissing_level_1_sets_onboarded_at(): void
    {
        $user = User::factory()->member()->create(['onboarded_at' => null, 'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', 1)
            ->call('dismiss')
            ->assertSet('level', null);

        $this->assertNotNull($user->fresh()->onboarded_at);
    }

    public function test_onboarded_user_sees_no_level_1_banner(): void
    {
        $user = User::factory()->member()->create(['onboarded_at' => now(), 'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', null)
            ->assertDontSee('Dit kan je nu allemaal');
    }

    public function test_contributor_with_published_fiche_sees_level_2_banner(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => now(),
            'contributor_onboarded_at' => null,
            'avatar_path' => null,
            'bio' => null,
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', 2)
            ->assertSee('eerste fiche gedeeld')
            ->assertSee('andere hartverwarmers');
    }

    public function test_dismissing_level_2_sets_contributor_onboarded_at(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => now(),
            'contributor_onboarded_at' => null,
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', 2)
            ->call('dismiss')
            ->assertSet('level', null);

        $this->assertNotNull($user->fresh()->contributor_onboarded_at);
    }

    public function test_fully_onboarded_user_sees_no_banner(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => now(),
            'contributor_onboarded_at' => now(),
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', null);
    }

    public function test_level_2_shows_profile_nudge(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => now(),
            'contributor_onboarded_at' => null,
            'avatar_path' => null,
            'bio' => null,
            'function_title' => 'Animator',
            'organisation' => 'WZC De Zon',
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', 2)
            ->assertSee('Stel je voor');
    }

    public function test_level_2_auto_dismisses_when_profile_complete(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => now(),
            'contributor_onboarded_at' => null,
            'avatar_path' => 'avatars/test.jpg',
            'bio' => 'Ik ben animator.',
            'function_title' => 'Animator',
            'organisation' => 'WZC De Zon',
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);
        $initiative = Initiative::factory()->published()->create();
        Fiche::factory()->published()->create([
            'initiative_id' => $initiative->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', null)
            ->assertDontSee('eerste fiche gedeeld');

        $this->assertNotNull($user->fresh()->contributor_onboarded_at);
    }

    public function test_onboarded_user_without_fiches_sees_no_level_2(): void
    {
        $user = User::factory()->create([
            'onboarded_at' => now(),
            'contributor_onboarded_at' => null,
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
        ]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', null);
    }

    public function test_existing_user_does_not_see_onboarding_banner(): void
    {
        $user = User::factory()->member()->create([
            'onboarded_at' => null,
            'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))->subDay(),
        ]);

        Livewire::actingAs($user)
            ->test(OnboardingBanner::class)
            ->assertSet('level', null);
    }
}
