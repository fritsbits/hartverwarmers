# DIAMANT Goals Beta Feature Flag Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a beta phase to the `diamant-goals` feature flag so only admins and Maite Mallentjer (user 2623) see DIAMANT goals, while keeping the admin toggle as the mechanism to go live for everyone.

**Architecture:** The resolver checks for a globally-activated null-scope row in the `features` table. If found, returns `true` for everyone (live mode). Otherwise, returns `true` only for beta testers (admins + Maite). The admin toggle activates/purges the null-scope row.

**Tech Stack:** Laravel 12, Laravel Pennant v1, PHPUnit

**Spec:** `docs/superpowers/specs/2026-03-16-diamant-goals-beta-flag-design.md`

---

### Task 1: Update DiamantGoals resolver to check global activation

**Files:**
- Modify: `app/Features/DiamantGoals.php`

- [ ] **Step 1: Update the resolver**

Replace the current resolver with one that checks for global activation via a direct DB query on the `features` table, then falls back to the beta tester list:

```php
<?php

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Attributes\Name;

#[Name('diamant-goals')]
class DiamantGoals
{
    private const ALLOWED_USER_IDS = [2623]; // Maite Mallentjer

    /**
     * Resolve the feature's initial value.
     *
     * Checks if globally activated (null-scope stored true) first.
     * Falls back to beta tester list (admins + allowed IDs).
     */
    public function resolve(?User $scope): bool
    {
        $globallyActive = DB::table('features')
            ->where('name', 'diamant-goals')
            ->where('scope', '__laravel_null')
            ->where('value', 'true')
            ->exists();

        if ($globallyActive) {
            return true;
        }

        if (! $scope) {
            return false;
        }

        return $scope->isAdmin() || in_array($scope->id, self::ALLOWED_USER_IDS);
    }
}
```

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
git add app/Features/DiamantGoals.php
git commit -m "feat: add global activation check to DiamantGoals resolver"
```

---

### Task 2: Update FeatureController toggle and index logic

**Files:**
- Modify: `app/Http/Controllers/FeatureController.php`

- [ ] **Step 1: Update the controller**

Replace the `index()` and `toggle()` methods. The `index()` method queries the DB directly for badge state. The `toggle()` method uses `Feature::for(null)->activate()` to go live and `Feature::purge()` to return to beta:

```php
<?php

namespace App\Http\Controllers;

use App\Features\DiamantGoals;
use App\Features\WizardDevMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class FeatureController extends Controller
{
    /**
     * Known features with display metadata.
     *
     * @var array<string, array{class: class-string, label: string, description: string}>
     */
    private const FEATURES = [
        'diamant-goals' => [
            'class' => DiamantGoals::class,
            'label' => 'DIAMANT-doelen',
            'description' => 'Toont de zeven DIAMANT-doelstellingen in navigatie, homepagina, fiches en formulieren.',
        ],
        'wizard-dev-mode' => [
            'class' => WizardDevMode::class,
            'label' => 'Wizard Dev Mode',
            'description' => 'Laat admins direct naar elke stap van de fiche-wizard springen met vooraf ingevulde testdata.',
        ],
    ];

    public function index(): View
    {
        $features = collect(self::FEATURES)->map(fn ($meta, $name) => [
            'name' => $name,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'globally_active' => $this->isGloballyActive($name),
            'active' => Feature::active($meta['class']),
        ]);

        return view('admin.features', ['features' => $features]);
    }

    public function toggle(string $feature): RedirectResponse
    {
        if (! isset(self::FEATURES[$feature])) {
            abort(404);
        }

        $class = self::FEATURES[$feature]['class'];

        if ($this->isGloballyActive($feature)) {
            // Back to beta: purge all stored values, resolver takes over
            Feature::purge($class);
            $status = 'uitgeschakeld';
        } else {
            // Go live: purge stale per-user values, then activate null scope
            Feature::purge($class);
            Feature::for(null)->activate($class);
            $status = 'ingeschakeld';
        }

        return redirect()->route('admin.features')
            ->with('success', self::FEATURES[$feature]['label']." is {$status}.");
    }

    /**
     * Check if a feature has been globally activated (null-scope stored true).
     * Queries DB directly to avoid triggering Pennant's resolve-and-store cycle.
     */
    private function isGloballyActive(string $featureName): bool
    {
        return DB::table('features')
            ->where('name', $featureName)
            ->where('scope', '__laravel_null')
            ->where('value', 'true')
            ->exists();
    }
}
```

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/FeatureController.php
git commit -m "feat: update toggle to use purge/activate for beta lifecycle"
```

---

### Task 3: Update admin features Blade view for beta badge

**Files:**
- Modify: `resources/views/admin/features.blade.php`

- [ ] **Step 1: Update the view**

Add a third badge state: "Beta" (yellow) when the feature is active for the admin (resolver returns true) but not globally activated. Replace the current badge logic:

```blade
<x-sidebar-layout title="Feature Flags" section-label="Beheer" description="Schakel onderdelen van de applicatie in of uit.">

    <div class="space-y-4">
        @foreach($features as $feature)
            <flux:card>
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-heading font-bold text-base">{{ $feature['label'] }}</span>
                            @if($feature['globally_active'])
                                <flux:badge size="sm" color="green" inset="top bottom">Actief</flux:badge>
                            @elseif($feature['active'])
                                <flux:badge size="sm" color="yellow" inset="top bottom">Beta</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" inset="top bottom">Inactief</flux:badge>
                            @endif
                        </div>
                        <p class="text-sm text-[var(--color-text-secondary)]">{{ $feature['description'] }}</p>
                    </div>

                    <form action="{{ route('admin.features.toggle', $feature['name']) }}" method="POST" class="shrink-0"
                          onsubmit="return confirm('{{ $feature['globally_active'] ? 'Weet je zeker dat je \'' . $feature['label'] . '\' wilt uitschakelen?' : 'Weet je zeker dat je \'' . $feature['label'] . '\' wilt inschakelen?' }}')">
                        @csrf
                        @if($feature['globally_active'])
                            <flux:button variant="ghost" type="submit" size="sm">Uitschakelen</flux:button>
                        @else
                            <flux:button variant="primary" type="submit" size="sm">Inschakelen</flux:button>
                        @endif
                    </form>
                </div>
            </flux:card>
        @endforeach
    </div>

</x-sidebar-layout>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/admin/features.blade.php
git commit -m "feat: add beta badge to admin features page"
```

---

### Task 4: Update existing toggle tests and add beta lifecycle tests

**Files:**
- Modify: `tests/Feature/FeatureFlagTest.php`

- [ ] **Step 1: Update the existing toggle-on test**

The existing `test_admin_can_toggle_feature_on` test (line 102-112) needs updating. After toggling on, the null scope should have a stored `true`:

```php
public function test_admin_can_toggle_feature_on(): void
{
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.features.toggle', 'diamant-goals'));

    $response->assertRedirect(route('admin.features'));
    $this->assertTrue(Feature::for(null)->active(DiamantGoals::class));
}
```

- [ ] **Step 2: Update the existing toggle-off test**

The existing `test_admin_can_toggle_feature_off` test (line 114-125) needs updating. After toggling off, the feature should be purged (resolver takes over). For null scope, resolver returns `false`. For admin, resolver returns `true`:

```php
public function test_admin_can_toggle_feature_off(): void
{
    // Simulate "live" state: null scope activated
    Feature::for(null)->activate(DiamantGoals::class);

    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.features.toggle', 'diamant-goals'));

    $response->assertRedirect(route('admin.features'));
    // Null scope should be purged — resolver returns false for null
    $this->assertFalse(Feature::for(null)->active(DiamantGoals::class));
    // But admin still sees it via resolver
    $this->assertTrue(Feature::for($admin)->active(DiamantGoals::class));
}
```

- [ ] **Step 3: Add beta lifecycle tests**

Add four new tests at the end of the class:

```php
public function test_beta_regular_user_cannot_see_goals(): void
{
    // Beta phase: no global activation
    Feature::purge(DiamantGoals::class);

    $user = User::factory()->create();

    $this->assertFalse(Feature::for($user)->active(DiamantGoals::class));
    $this->actingAs($user)->get(route('goals.index'))->assertStatus(404);
}

public function test_beta_admin_can_see_goals(): void
{
    // Beta phase: no global activation
    Feature::purge(DiamantGoals::class);

    $admin = User::factory()->admin()->create();

    $this->assertTrue(Feature::for($admin)->active(DiamantGoals::class));
    $this->actingAs($admin)->get(route('goals.index'))->assertStatus(200);
}

public function test_live_regular_user_can_see_goals(): void
{
    // Live phase: null scope activated
    Feature::purge(DiamantGoals::class);
    Feature::for(null)->activate(DiamantGoals::class);

    $user = User::factory()->create();

    $this->assertTrue(Feature::for($user)->active(DiamantGoals::class));
    $this->actingAs($user)->get(route('goals.index'))->assertStatus(200);
}

public function test_back_to_beta_regular_user_loses_access(): void
{
    // Was live, now back to beta
    Feature::for(null)->activate(DiamantGoals::class);
    Feature::purge(DiamantGoals::class);

    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->assertFalse(Feature::for($user)->active(DiamantGoals::class));
    $this->assertTrue(Feature::for($admin)->active(DiamantGoals::class));
}
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --compact tests/Feature/FeatureFlagTest.php`
Expected: All tests pass

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/FeatureFlagTest.php
git commit -m "test: add beta/live lifecycle tests for diamant-goals"
```

---

### Task 5: Purge stale Pennant values and push

- [ ] **Step 1: Purge stale stored values locally**

Run: `php artisan tinker --execute="Laravel\Pennant\Feature::purge('diamant-goals'); echo 'Purged';"`

- [ ] **Step 2: Run full feature flag test suite one more time**

Run: `php artisan test --compact tests/Feature/FeatureFlagTest.php`
Expected: All tests pass

- [ ] **Step 3: Push**

```bash
git push
```

Note: On production, run `php artisan pennant:purge diamant-goals` after deploy (one-time) to clear stale stored values.
