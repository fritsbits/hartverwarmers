# Whats-New Page & Banner Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a static "/wat-is-er-nieuw" page and a dismissable homepage banner to inform returning users about the rebuilt platform.

**Architecture:** Static Blade view for the page (matching `about.blade.php` pattern), anonymous Blade component for the banner with Alpine.js dismiss logic and localStorage persistence. Mutually exclusive with existing Livewire onboarding banner based on user `created_at` vs launch date config.

**Tech Stack:** Laravel 12, Blade, Flux UI Pro (callout, badge), Alpine.js, Tailwind CSS v4

**Spec:** `docs/superpowers/specs/2026-03-16-whats-new-page-banner-design.md`

---

## Chunk 1: Config & Route Setup

### Task 1: Create config file

**Files:**
- Create: `config/hartverwarmers.php`

- [ ] **Step 1: Create the config file**

```php
<?php

return [
    'launch_date' => env('HARTVERWARMERS_LAUNCH_DATE', '2026-03-19'),
];
```

- [ ] **Step 2: Verify config loads**

Run: `php artisan tinker --execute="echo config('hartverwarmers.launch_date');"`
Expected: `2026-03-19`

- [ ] **Step 3: Commit**

```bash
git add config/hartverwarmers.php
git commit -m "feat: add hartverwarmers config with launch_date"
```

### Task 2: Add the route

**Files:**
- Modify: `routes/web.php:111-112` (near the other `Route::view()` declarations)

- [ ] **Step 1: Write failing test for the route**

Create `tests/Feature/WhatsNewPageTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsNewPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_whats_new_page_loads(): void
    {
        $response = $this->get('/wat-is-er-nieuw');

        $response->assertStatus(200);
    }

    public function test_whats_new_page_contains_heading(): void
    {
        $response = $this->get('/wat-is-er-nieuw');

        $response->assertSee('Een nieuwe Hartverwarmers');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/WhatsNewPageTest.php`
Expected: FAIL (404 — route doesn't exist yet)

- [ ] **Step 3: Add the route to web.php**

Add this line in `routes/web.php` right before the `Route::view('/over-ons', ...)` line (around line 112):

```php
Route::view('/wat-is-er-nieuw', 'wat-is-er-nieuw')->name('whats-new');
```

- [ ] **Step 4: Create a minimal placeholder view so the route resolves**

Create `resources/views/wat-is-er-nieuw.blade.php` with just:

```blade
<x-layout title="Wat is er nieuw" description="Ontdek wat er veranderd is op Hartverwarmers: nieuwe structuur, betere navigatie en automatische PDF-conversie." :full-width="true">
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Nieuw</span>
                <h1 class="mt-1">Een nieuwe Hartverwarmers. Gebouwd door jullie.</h1>
            </div>
        </div>
    </section>
</x-layout>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/WhatsNewPageTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add routes/web.php resources/views/wat-is-er-nieuw.blade.php tests/Feature/WhatsNewPageTest.php
git commit -m "feat: add /wat-is-er-nieuw route and placeholder view"
```

---

## Chunk 2: Full Page Content

### Task 3: Build the complete whats-new page

**Files:**
- Modify: `resources/views/wat-is-er-nieuw.blade.php`

**Reference:** Follow the structure of `resources/views/about.blade.php` — hero with cream bg, then content sections with `<hr>` dividers, `max-w-6xl` outer container, `max-w-3xl` on text blocks.

**Flux components to use:**
- `<flux:callout icon="information-circle">` with `<flux:callout.text>` for the "je moet opnieuw inloggen" block
- `<flux:badge color="lime">Binnenkort</flux:badge>` for the DIAMANT teaser

- [ ] **Step 1: Replace placeholder with full content**

Replace the entire content of `resources/views/wat-is-er-nieuw.blade.php` with the complete page. Use the verbatim text from the briefing. Structure:

```blade
<x-layout title="Wat is er nieuw" description="Ontdek wat er veranderd is op Hartverwarmers: nieuwe structuur, betere navigatie en automatische PDF-conversie." :full-width="true">

    {{-- Hero / Block 1: Opening --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Nieuw</span>
                <h1 class="mt-1">Een nieuwe Hartverwarmers. Gebouwd door jullie.</h1>
                <div class="text-[var(--color-text-secondary)] mt-6 space-y-4" style="font-weight: var(--font-weight-light);">
                    <p>Bijna 500 activiteiten. Dat hebben jullie samen opgebouwd, activiteitenbegeleiders uit heel Vlaanderen en Nederland. Vaak zonder dat iemand van ons daar iets voor deed. Dat verdient erkenning.</p>
                    <p>Het verhaal van Hartverwarmers begon in maart 2020, in de eerste week van de lockdown. Frederik Vincx bouwde het platform in één week, samen met Maite Mallentjer. Wat begon als een crisisinitiatief, groeide uit tot een community van meer dan 4.800 collega's. Na 2022 liep het platform even op de achtergrond. De community bleef groeien, maar de beheerders hadden minder tijd en middelen. Nu slaan Frederik en Maite opnieuw de handen in elkaar. Meer over hun verhaal lees je op de <a href="{{ route('about') }}" class="underline hover:text-[var(--color-primary)]">over ons-pagina</a>.</p>
                    <p>Dit is de eerste versie van de vernieuwde website. En er komt nog meer aan.</p>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 2: Eerst het praktische --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <h2>Eerst het praktische</h2>
                <div class="mt-6">
                    <flux:callout icon="information-circle">
                        <flux:callout.text>
                            Je moet opnieuw inloggen. Dat is normaal, de website is volledig herbouwd. Je bestaand wachtwoord werkt gewoon nog. Alles wat er stond, is er nog.
                        </flux:callout.text>
                    </flux:callout>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 3: Wat er anders is --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <h2>Wat er anders is</h2>
                <div class="text-[var(--color-text-secondary)] mt-6 space-y-8" style="font-weight: var(--font-weight-light);">
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Initiatieven en fiches</h3>
                        <p class="mt-2">De structuur is iets veranderd, en dat vraagt even gewenning. Activiteiten zijn nu gegroepeerd onder initiatieven, een breed concept zoals "Quiz" of "Muziek". Een concrete uitwerking van een collega noemen we een fiche. Zo zie je in één oogopslag wat het initiatief is én hoe anderen het al hebben toegepast.</p>
                    </div>
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Geen foto-upload meer</h3>
                        <p class="mt-2">Eén ding is ook verdwenen: je kan geen eigen foto meer toevoegen aan een fiche. Tot twee keer toe moesten we boetes betalen omdat er afbeeldingen waren opgeladen waarvoor de rechten niet klopten. Dat willen we niemand aandoen, en het was niet meer houdbaar.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 4: Wat er beter is --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <h2>Wat er beter is</h2>
                <div class="text-[var(--color-text-secondary)] mt-6 space-y-8" style="font-weight: var(--font-weight-light);">
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Beter bladeren en previews</h3>
                        <p class="mt-2">Bladeren gaat een stuk vlotter. Je ziet meteen previews van wat er in een fiche zit, zonder eerst te moeten downloaden. En als je even wegklikt, vind je gemakkelijk de weg terug.</p>
                    </div>
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Automatische PDF-conversie</h3>
                        <p class="mt-2">Bestanden openen lukt nu ook voor iedereen. Vroeger zagen we regelmatig in de reacties dat mensen een PowerPoint niet konden openen. Nu zetten we elk bestand automatisch om naar PDF. Simpel, maar het scheelt een hoop gedoe.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 5: Wat er aankomt --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <h2>Wat er aankomt</h2>
                <p class="text-[var(--color-text-secondary)] mt-6" style="font-weight: var(--font-weight-light);">
                    Binnenkort introduceren we het DIAMANT-model, een kwaliteitskader rond zinvolle activiteiten in woonzorgcentra, ontwikkeld vanuit de expertise van Maite Mallentjer. Meer daarover volgt.
                </p>
                <div class="mt-4">
                    <flux:badge color="lime">Binnenkort</flux:badge>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 6: Jouw feedback telt --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <h2>Jouw feedback telt</h2>
                <div class="text-[var(--color-text-secondary)] mt-6 space-y-4" style="font-weight: var(--font-weight-light);">
                    <p>We hebben al gebruikerstests gedaan met echte activiteitenbegeleiders, en wat we leerden hebben we meteen verwerkt. Maar we zijn er nog niet. Heb jij een suggestie, een vraag, of iets dat niet klopt?</p>
                    <a href="mailto:info@hartverwarmers.be" class="cta-link inline-block">Stuur een mailtje</a>
                    <p>We lezen alles.</p>
                    <p>En dan nog dit: we zijn benieuwd welke activiteit de 500ste wordt. Die mijlpaal is van jullie, niet van ons.</p>
                </div>
            </div>
        </div>
    </section>

</x-layout>
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --compact tests/Feature/WhatsNewPageTest.php`
Expected: PASS

- [ ] **Step 3: Build frontend and take screenshot to verify visually**

Run: `npm run build`
Then: `node scripts/screenshot.cjs /wat-is-er-nieuw /tmp/whats-new.png`
Verify: Read the screenshot image to check layout, typography, spacing.

- [ ] **Step 4: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Commit**

```bash
git add resources/views/wat-is-er-nieuw.blade.php
git commit -m "feat: add full content to whats-new page"
```

---

## Chunk 3: Banner Component & Homepage Integration

### Task 4: Create the whats-new banner component

**Files:**
- Create: `resources/views/components/whats-new-banner.blade.php`

- [ ] **Step 1: Write failing test for banner visibility**

Add to `tests/Feature/WhatsNewPageTest.php`:

```php
public function test_guest_sees_whats_new_banner_on_homepage(): void
{
    $response = $this->get('/');

    $response->assertSee('Hartverwarmers is volledig vernieuwd');
    $response->assertSee('Lees meer');
}

public function test_existing_user_sees_whats_new_banner(): void
{
    $user = User::factory()->create([
        'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))->subDay(),
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertSee('Hartverwarmers is volledig vernieuwd');
}

public function test_new_user_does_not_see_whats_new_banner(): void
{
    $user = User::factory()->create([
        'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
}

public function test_new_user_sees_onboarding_banner_instead(): void
{
    $user = User::factory()->create([
        'onboarded_at' => null,
        'created_at' => Carbon::parse(config('hartverwarmers.launch_date')),
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertDontSee('Hartverwarmers is volledig vernieuwd');
    $response->assertSee('Dit kan je nu allemaal');
}
```

Add the necessary imports at the top of the test file:

```php
use App\Models\User;
use Carbon\Carbon;
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/WhatsNewPageTest.php`
Expected: FAIL (banner component doesn't exist yet)

- [ ] **Step 3: Create the banner component**

Create `resources/views/components/whats-new-banner.blade.php`:

```blade
@php
    $isNewUser = auth()->check() && auth()->user()->created_at->gte(\Carbon\Carbon::parse(config('hartverwarmers.launch_date')));
@endphp

@unless($isNewUser)
    <div
        x-data="{ show: !localStorage.getItem('whatsNewDismissed') }"
        x-show="show"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="bg-[var(--color-bg-cream)] border-b border-[var(--color-border-light)]"
    >
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center gap-4">
            <p class="flex-1 text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">
                Hartverwarmers is volledig vernieuwd. Ontdek wat er veranderd is.
                <a href="{{ route('whats-new') }}" class="cta-link ml-2">Lees meer</a>
            </p>
            <button
                @click="show = false; localStorage.setItem('whatsNewDismissed', 'true')"
                class="shrink-0 p-1 text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] transition-colors cursor-pointer"
                aria-label="Sluiten"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endunless
```

- [ ] **Step 4: Add banner to homepage**

In `resources/views/home.blade.php`, replace:

```blade
    <!-- Onboarding Banner -->
    <livewire:onboarding-banner />
```

With:

```blade
    <!-- Banners (mutually exclusive: whats-new for returning users, onboarding for new users) -->
    <x-whats-new-banner />
    <livewire:onboarding-banner />
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact tests/Feature/WhatsNewPageTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/whats-new-banner.blade.php resources/views/home.blade.php tests/Feature/WhatsNewPageTest.php
git commit -m "feat: add whats-new dismissable banner to homepage"
```

### Task 5: Update OnboardingBanner to skip existing users

**Files:**
- Modify: `app/Livewire/OnboardingBanner.php:15-19` (top of `mount()`)
- Modify: `tests/Feature/OnboardingBannerTest.php`

- [ ] **Step 1: Write failing test**

Add to `tests/Feature/OnboardingBannerTest.php`:

```php
public function test_existing_user_does_not_see_onboarding_banner(): void
{
    $user = User::factory()->member()->create([
        'onboarded_at' => null,
        'created_at' => \Carbon\Carbon::parse(config('hartverwarmers.launch_date'))->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(OnboardingBanner::class)
        ->assertSet('level', null);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=test_existing_user_does_not_see_onboarding_banner`
Expected: FAIL (level is 1, not null — existing user without onboarded_at still triggers level 1)

- [ ] **Step 3: Add guard to OnboardingBanner mount()**

In `app/Livewire/OnboardingBanner.php`, add this check right after the existing `if (! auth()->check()) { return; }` block (around line 19), before the level assignment logic:

```php
// Existing users (pre-launch) see the whats-new banner instead
if ($user->created_at->lt(\Carbon\Carbon::parse(config('hartverwarmers.launch_date')))) {
    return;
}
```

The `$user = auth()->user();` line that follows the auth check must come BEFORE this new guard. The resulting order should be:

```php
public function mount(): void
{
    if (! auth()->check()) {
        return;
    }

    $user = auth()->user();

    // Existing users (pre-launch) see the whats-new banner instead
    if ($user->created_at->lt(\Carbon\Carbon::parse(config('hartverwarmers.launch_date')))) {
        return;
    }

    if ($user->onboarded_at === null) {
        // ... rest unchanged
```

- [ ] **Step 4: Run the new test**

Run: `php artisan test --compact --filter=test_existing_user_does_not_see_onboarding_banner`
Expected: PASS

- [ ] **Step 5: Fix existing onboarding tests for launch date guard**

The new guard in `mount()` checks `created_at < launch_date`. Since `User::factory()` defaults `created_at` to `now()` and today (2026-03-16) is BEFORE the launch date (2026-03-19), all factory users will be treated as "existing/pre-launch" and the guard will skip them. Every existing test that expects onboarding behavior needs `'created_at'` set to a date on or after launch.

Add this import at the top of `tests/Feature/OnboardingBannerTest.php`:

```php
use Carbon\Carbon;
```

Then add `'created_at' => Carbon::parse(config('hartverwarmers.launch_date'))` to every `User::factory()->create()` call in these existing tests:
- `test_new_user_sees_level_1_banner`
- `test_dismissing_level_1_sets_onboarded_at`
- `test_onboarded_user_sees_no_level_1_banner`
- `test_contributor_with_published_fiche_sees_level_2_banner`
- `test_dismissing_level_2_sets_contributor_onboarded_at`
- `test_fully_onboarded_user_sees_no_banner`
- `test_level_2_shows_profile_nudge`
- `test_level_2_auto_dismisses_when_profile_complete`
- `test_onboarded_user_without_fiches_sees_no_level_2`

The `test_guest_sees_no_banner` test does not create a user, so it needs no change.

- [ ] **Step 6: Run all onboarding banner tests to check for regressions**

Run: `php artisan test --compact tests/Feature/OnboardingBannerTest.php`
Expected: ALL PASS

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/OnboardingBanner.php tests/Feature/OnboardingBannerTest.php
git commit -m "feat: skip onboarding banner for pre-launch users"
```

---

## Chunk 4: Visual Verification & Final Tests

### Task 6: Visual verification

- [ ] **Step 1: Build frontend assets**

Run: `npm run build`

- [ ] **Step 2: Screenshot the whats-new page**

Run: `node scripts/screenshot.cjs /wat-is-er-nieuw /tmp/whats-new.png`
Verify: Read the screenshot. Check hero section, content blocks, callout, badge, typography, spacing.

- [ ] **Step 3: Screenshot the homepage (as guest) to see the banner**

Run: `node scripts/screenshot.cjs / /tmp/home-banner.png`
Verify: Read the screenshot. Banner should appear below hero with text, link, and close button.

- [ ] **Step 4: Screenshot mobile views**

Run: `node scripts/screenshot.cjs /wat-is-er-nieuw /tmp/whats-new-mobile.png --mobile`
Run: `node scripts/screenshot.cjs / /tmp/home-banner-mobile.png --mobile`
Verify: Read screenshots. Check responsive layout — banner text should stack, close button stays right.

### Task 7: Run full test suite

- [ ] **Step 1: Run all related tests**

Run: `php artisan test --compact tests/Feature/WhatsNewPageTest.php tests/Feature/OnboardingBannerTest.php tests/Feature/HomeControllerTest.php`
Expected: ALL PASS

- [ ] **Step 2: Ask user if they want to run the full test suite**
