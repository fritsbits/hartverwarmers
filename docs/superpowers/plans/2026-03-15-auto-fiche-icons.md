# Auto Fiche Icons Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Automatically assign contextual Lucide icons to fiches and display them in colored discs for visual distinction.

**Architecture:** A Laravel AI agent (`IconSelector`) picks the best Lucide icon for each fiche. A queued job dispatches on fiche create/title-update via model observer. The icon renders in a `<x-fiche-icon>` Blade component with deterministic ID-based colors.

**Tech Stack:** Laravel 12, laravel/ai (Anthropic provider), blade-lucide-icons, Tailwind CSS v4

**Spec:** `docs/superpowers/specs/2026-03-15-auto-fiche-icons-design.md`

---

## Chunk 1: Foundation (Database, Config, Dependencies)

### Task 1: Install blade-lucide-icons package

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Install the package**

```bash
composer require blade-ui-kit/blade-lucide-icons
```

- [ ] **Step 2: Verify installation**

```bash
php artisan view:clear
```

Confirm no errors. The package auto-discovers — no service provider registration needed.

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: install blade-lucide-icons package"
```

---

### Task 2: Add icon column to fiches table

**Files:**
- Create: `database/migrations/xxxx_add_icon_to_fiches_table.php`
- Modify: `app/Models/Fiche.php` (add `icon` to `$fillable`)
- Modify: `database/factories/FicheFactory.php` (add `withIcon` state)

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration add_icon_to_fiches_table --table=fiches --no-interaction
```

- [ ] **Step 2: Write migration content**

In the generated migration file:

```php
public function up(): void
{
    Schema::table('fiches', function (Blueprint $table) {
        $table->string('icon')->nullable()->after('featured_month');
    });
}

public function down(): void
{
    Schema::table('fiches', function (Blueprint $table) {
        $table->dropColumn('icon');
    });
}
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate
```

- [ ] **Step 4: Add `icon` to Fiche model `$fillable`**

In `app/Models/Fiche.php`, add `'icon'` to the `$fillable` array after `'featured_month'`.

- [ ] **Step 5: Add `withIcon` factory state**

In `database/factories/FicheFactory.php`, add:

```php
public function withIcon(string $icon = 'file-text'): static
{
    return $this->state(fn (array $attributes) => [
        'icon' => $icon,
    ]);
}
```

- [ ] **Step 6: Commit**

```bash
git add database/migrations/*add_icon_to_fiches_table* app/Models/Fiche.php database/factories/FicheFactory.php
git commit -m "feat: add icon column to fiches table"
```

---

### Task 3: Create fiche icons config

**Files:**
- Create: `config/fiche-icons.php`

- [ ] **Step 1: Review existing fiche titles for theme coverage**

```bash
php artisan tinker --execute="echo App\Models\Fiche::pluck('title')->implode(\"\\n\")"
```

Use the output to inform which icons to include in the allowlist.

- [ ] **Step 2: Create config file**

Create `config/fiche-icons.php` with the curated icon allowlist and color palette:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Icon Allowlist
    |--------------------------------------------------------------------------
    |
    | Curated subset of Lucide icon names relevant to elderly care activities.
    | The AI agent picks from this list only — prevents inappropriate icons.
    |
    */

    'allowlist' => [
        // Music & performance
        'music', 'mic', 'guitar', 'piano', 'headphones', 'radio', 'disc-3',

        // Nature & outdoors
        'flower-2', 'trees', 'sun', 'cloud-sun', 'bird', 'leaf', 'sprout', 'mountain',

        // Food & cooking
        'cooking-pot', 'utensils', 'cake', 'apple', 'salad', 'coffee', 'wine', 'ice-cream-cone',

        // Arts & crafts
        'palette', 'scissors', 'paintbrush', 'pen-tool', 'brush', 'stamp',

        // Games & puzzles
        'puzzle', 'dice-5', 'trophy', 'target', 'gamepad-2', 'spade',

        // Health & movement
        'heart-pulse', 'footprints', 'bike', 'dumbbell', 'smile', 'hand',

        // Social & celebration
        'party-popper', 'gift', 'heart', 'users', 'handshake', 'message-circle',

        // Learning & memory
        'brain', 'book-open', 'lightbulb', 'graduation-cap', 'newspaper', 'file-question',

        // Seasons & holidays
        'snowflake', 'egg', 'star', 'candle', 'flame', 'umbrella',

        // Animals
        'dog', 'cat', 'fish', 'rabbit', 'squirrel',

        // Home & domestic
        'home', 'armchair', 'lamp', 'shirt', 'bath',

        // Travel & places
        'camera', 'map', 'globe', 'compass', 'train', 'car',

        // Time & calendar
        'clock', 'calendar', 'hourglass',

        // General
        'flag', 'sparkles', 'wand-2', 'megaphone', 'tv', 'clapperboard',
        'drama', 'church', 'crown', 'gem', 'feather', 'ribbon',

        // Fallback
        'file-text',
    ],

    /*
    |--------------------------------------------------------------------------
    | Color Palette
    |--------------------------------------------------------------------------
    |
    | 6 deterministic colors for icon discs, assigned by fiche ID % 6.
    | Colors 0-3 match the user avatar palette. 4-5 are additions.
    |
    */

    'colors' => [
        ['bg' => '#FDF3EE', 'text' => '#E8764B'],  // 0: orange
        ['bg' => '#E8F6F8', 'text' => '#3A9BA8'],  // 1: teal
        ['bg' => '#FEF6E0', 'text' => '#B08A22'],  // 2: yellow
        ['bg' => '#F3E8F3', 'text' => '#9A5E98'],  // 3: purple
        ['bg' => '#E8F5E9', 'text' => '#4A8C5C'],  // 4: green
        ['bg' => '#FDE8EC', 'text' => '#C0506A'],  // 5: rose
    ],
];
```

- [ ] **Step 3: Commit**

```bash
git add config/fiche-icons.php
git commit -m "feat: add fiche icons config with allowlist and color palette"
```

---

## Chunk 2: AI Agent and Job

### Task 4: Create the IconSelector AI agent

**Files:**
- Create: `app/Ai/Agents/IconSelector.php`
- Test: `tests/Feature/Ai/IconSelectorTest.php`

- [ ] **Step 1: Create the agent class**

```bash
php artisan make:agent IconSelector --no-interaction
```

- [ ] **Step 2: Write the agent**

In `app/Ai/Agents/IconSelector.php`:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[UseCheapestModel]
#[MaxTokens(50)]
#[Temperature(0)]
class IconSelector implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $icons = implode(', ', config('fiche-icons.allowlist'));

        return <<<PROMPT
        You are an icon selector for a Dutch elderly care activities platform.
        Given an activity title, pick the single most representative icon name from this list:

        {$icons}

        Rules:
        - Return ONLY the icon name, nothing else. No quotes, no explanation.
        - If no icon fits well, return "file-text".
        - The activity titles are in Dutch. Common themes: music, crafts, cooking, nature, games, holidays, movement, memory exercises.
        PROMPT;
    }
}
```

- [ ] **Step 3: Write the test**

```bash
php artisan make:test Ai/IconSelectorTest --phpunit --no-interaction
```

In `tests/Feature/Ai/IconSelectorTest.php`:

```php
<?php

namespace Tests\Feature\Ai;

use App\Ai\Agents\IconSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IconSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function testSelectsIconForMusicActivity(): void
    {
        IconSelector::fake(fn (string $prompt) => 'music');

        $response = (new IconSelector)->prompt('Eurosong quiz');

        $this->assertEquals('music', (string) $response);
    }

    public function testSelectsFallbackForUnknownActivity(): void
    {
        IconSelector::fake(fn (string $prompt) => 'file-text');

        $response = (new IconSelector)->prompt('Algemene activiteit');

        $this->assertEquals('file-text', (string) $response);
    }

    public function testInstructionsContainAllowlist(): void
    {
        $agent = new IconSelector;
        $instructions = $agent->instructions();

        $this->assertStringContainsString('music', $instructions);
        $this->assertStringContainsString('flower-2', $instructions);
        $this->assertStringContainsString('file-text', $instructions);
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=IconSelector
```

Expected: 3 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Ai/Agents/IconSelector.php tests/Feature/Ai/IconSelectorTest.php
git commit -m "feat: add IconSelector AI agent for fiche icon assignment"
```

---

### Task 5: Create the AssignFicheIcon job

**Files:**
- Create: `app/Jobs/AssignFicheIcon.php`
- Test: `tests/Feature/Jobs/AssignFicheIconTest.php`

- [ ] **Step 1: Create the job**

```bash
php artisan make:job AssignFicheIcon --no-interaction
```

- [ ] **Step 2: Write the job**

In `app/Jobs/AssignFicheIcon.php`:

```php
<?php

namespace App\Jobs;

use App\Ai\Agents\IconSelector;
use App\Models\Fiche;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class AssignFicheIcon implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public Fiche $fiche) {}

    public function handle(): void
    {
        $prompt = $this->fiche->title;

        if ($this->fiche->description) {
            $prompt .= "\n\nBeschrijving: " . Str::limit(strip_tags($this->fiche->description), 200);
        }

        $response = (new IconSelector)->prompt($prompt);
        $icon = trim((string) $response);

        $allowlist = config('fiche-icons.allowlist');

        if (! in_array($icon, $allowlist)) {
            $icon = 'file-text';
        }

        $this->fiche->updateQuietly(['icon' => $icon]);
    }
}
```

Note: `updateQuietly` prevents the observer from re-dispatching the job.

- [ ] **Step 3: Write the test**

```bash
php artisan make:test Jobs/AssignFicheIconTest --phpunit --no-interaction
```

In `tests/Feature/Jobs/AssignFicheIconTest.php`:

```php
<?php

namespace Tests\Feature\Jobs;

use App\Ai\Agents\IconSelector;
use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignFicheIconTest extends TestCase
{
    use RefreshDatabase;

    public function testAssignsIconFromAiResponse(): void
    {
        IconSelector::fake(fn (string $prompt) => 'music');

        $fiche = Fiche::factory()->create(['title' => 'Eurosong quiz']);

        // Reset icon since observer may have set it
        $fiche->updateQuietly(['icon' => null]);

        (new AssignFicheIcon($fiche))->handle();

        $this->assertEquals('music', $fiche->fresh()->icon);
    }

    public function testFallsBackToFileTextForInvalidIcon(): void
    {
        IconSelector::fake(fn (string $prompt) => 'nonexistent-icon');

        $fiche = Fiche::factory()->create(['title' => 'Test activiteit']);
        $fiche->updateQuietly(['icon' => null]);

        (new AssignFicheIcon($fiche))->handle();

        $this->assertEquals('file-text', $fiche->fresh()->icon);
    }

    public function testIncludesDescriptionInPrompt(): void
    {
        IconSelector::fake(fn (string $prompt) => 'flower-2');

        $fiche = Fiche::factory()->create([
            'title' => 'Bloemen schikken',
            'description' => '<p>Een gezellige activiteit met verse bloemen.</p>',
        ]);
        $fiche->updateQuietly(['icon' => null]);

        (new AssignFicheIcon($fiche))->handle();

        IconSelector::assertPrompted(function ($prompt) {
            return $prompt->contains('Bloemen schikken')
                && $prompt->contains('Een gezellige activiteit');
        });
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=AssignFicheIcon
```

Expected: 3 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/AssignFicheIcon.php tests/Feature/Jobs/AssignFicheIconTest.php
git commit -m "feat: add AssignFicheIcon queued job"
```

---

### Task 6: Create the Fiche observer

**Files:**
- Create: `app/Observers/FicheObserver.php`
- Modify: `app/Models/Fiche.php` (add `ObservedBy` attribute)
- Test: `tests/Feature/Observers/FicheObserverTest.php`

- [ ] **Step 1: Create the observer**

```bash
php artisan make:observer FicheObserver --model=Fiche --no-interaction
```

- [ ] **Step 2: Write the observer**

In `app/Observers/FicheObserver.php`:

```php
<?php

namespace App\Observers;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;

class FicheObserver
{
    public function created(Fiche $fiche): void
    {
        AssignFicheIcon::dispatch($fiche);
    }

    public function updated(Fiche $fiche): void
    {
        if ($fiche->isDirty('title')) {
            AssignFicheIcon::dispatch($fiche);
        }
    }
}
```

- [ ] **Step 3: Register the observer on the model**

In `app/Models/Fiche.php`, add the `ObservedBy` attribute:

```php
use App\Observers\FicheObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([FicheObserver::class])]
class Fiche extends Model
```

- [ ] **Step 4: Write the test**

```bash
php artisan make:test Observers/FicheObserverTest --phpunit --no-interaction
```

In `tests/Feature/Observers/FicheObserverTest.php`:

```php
<?php

namespace Tests\Feature\Observers;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FicheObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testDispatchesJobOnFicheCreation(): void
    {
        Queue::fake();

        $fiche = Fiche::factory()->create();

        Queue::assertPushed(AssignFicheIcon::class, function ($job) use ($fiche) {
            return $job->fiche->id === $fiche->id;
        });
    }

    public function testDispatchesJobOnTitleUpdate(): void
    {
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->create());

        Queue::fake();

        $fiche->update(['title' => 'Nieuwe titel']);

        Queue::assertPushed(AssignFicheIcon::class, function ($job) use ($fiche) {
            return $job->fiche->id === $fiche->id;
        });
    }

    public function testDoesNotDispatchJobWhenTitleUnchanged(): void
    {
        $fiche = Fiche::withoutEvents(fn () => Fiche::factory()->create());

        Queue::fake();

        $fiche->update(['description' => 'Updated description only']);

        Queue::assertNotPushed(AssignFicheIcon::class);
    }
}
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=FicheObserver
```

Expected: 3 tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Observers/FicheObserver.php app/Models/Fiche.php tests/Feature/Observers/FicheObserverTest.php
git commit -m "feat: add FicheObserver to dispatch icon assignment on create/title change"
```

---

## Chunk 3: UI Components and View Updates

### Task 7: Create the `<x-fiche-icon>` Blade component

**Files:**
- Create: `resources/views/components/fiche-icon.blade.php`
- Test: `tests/Feature/Components/FicheIconTest.php`

- [ ] **Step 1: Write the test**

```bash
php artisan make:test Components/FicheIconTest --phpunit --no-interaction
```

In `tests/Feature/Components/FicheIconTest.php`:

```php
<?php

namespace Tests\Feature\Components;

use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FicheIconTest extends TestCase
{
    use RefreshDatabase;

    public function testRendersLucideIconWhenIconIsSet(): void
    {
        $fiche = Fiche::factory()->withIcon('music')->create();

        $view = $this->blade('<x-fiche-icon :fiche="$fiche" />', ['fiche' => $fiche]);

        $view->assertSee('lucide-music', false);
    }

    public function testRendersFallbackWhenIconIsNull(): void
    {
        $fiche = Fiche::factory()->create(['icon' => null]);

        $view = $this->blade('<x-fiche-icon :fiche="$fiche" />', ['fiche' => $fiche]);

        // Fallback should NOT render a Lucide component
        $view->assertDontSee('lucide-', false);
        // But should render an SVG
        $view->assertSee('<svg', false);
    }

    public function testAssignsDeterministicColor(): void
    {
        $fiche = Fiche::factory()->withIcon('music')->create();

        $view = $this->blade('<x-fiche-icon :fiche="$fiche" />', ['fiche' => $fiche]);

        $colors = config('fiche-icons.colors');
        $expected = $colors[$fiche->id % 6];
        $view->assertSee($expected['bg'], false);
    }

    public function testRendersDifferentSizes(): void
    {
        $fiche = Fiche::factory()->withIcon('heart')->create();

        $viewSm = $this->blade('<x-fiche-icon :fiche="$fiche" size="sm" />', ['fiche' => $fiche]);
        $viewSm->assertSee('w-8', false);

        $viewLg = $this->blade('<x-fiche-icon :fiche="$fiche" size="lg" />', ['fiche' => $fiche]);
        $viewLg->assertSee('w-16', false);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=FicheIcon
```

Expected: FAIL — component does not exist yet.

- [ ] **Step 3: Create the component**

Create `resources/views/components/fiche-icon.blade.php`:

```blade
@props(['fiche', 'size' => 'md'])

@php
    $colors = config('fiche-icons.colors');
    $color = $colors[($fiche->id ?? 0) % count($colors)];

    $sizeMap = [
        'sm' => ['disc' => 'w-8 h-8', 'icon' => 'w-4 h-4'],
        'md' => ['disc' => 'w-12 h-12', 'icon' => 'w-6 h-6'],
        'lg' => ['disc' => 'w-16 h-16', 'icon' => 'w-8 h-8'],
    ];
    $sizes = $sizeMap[$size] ?? $sizeMap['md'];
@endphp

<div {{ $attributes->merge(['class' => "{$sizes['disc']} rounded-full flex items-center justify-center shrink-0"]) }}
     style="background-color: {{ $color['bg'] }}; color: {{ $color['text'] }}">
    @if($fiche->icon)
        <x-dynamic-component :component="'lucide-' . $fiche->icon" :class="$sizes['icon']" />
    @else
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="{{ $sizes['icon'] }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
    @endif
</div>
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=FicheIcon
```

Expected: 4 tests pass.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/fiche-icon.blade.php tests/Feature/Components/FicheIconTest.php
git commit -m "feat: add fiche-icon Blade component with colored discs"
```

---

### Task 8: Update CSS for fiche list items

**Files:**
- Modify: `resources/css/app.css` (update `.fiche-list-icon`, remove orange hover)

- [ ] **Step 1: Update `.fiche-list-icon` class**

In `resources/css/app.css`, replace the existing `.fiche-list-icon` block (lines ~698-716) with:

```css
  .fiche-list-icon {
    @apply flex items-center justify-center shrink-0;
    width: 48px;
    height: 48px;
    border-radius: 50%;
  }

  .fiche-list-icon svg {
    width: 24px;
    height: 24px;
  }
```

Remove the hover rule entirely:

```css
  /* DELETE this block: */
  .fiche-list-item:hover .fiche-list-icon {
    color: var(--color-primary);
    background: #fde8dc;
  }
```

- [ ] **Step 2: Build frontend assets**

```bash
npm run build
```

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: update fiche list icon styles — 48px circle, remove orange hover"
```

---

### Task 9: Update initiative show view

**Files:**
- Modify: `resources/views/initiatives/show.blade.php` (~lines 196-223)

- [ ] **Step 1: Replace fiche list item markup**

In `resources/views/initiatives/show.blade.php`, replace the fiche list item content (the `<a>` element inside the `@foreach`) with:

```blade
<a
    href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}"
    class="fiche-list-item"
    x-show="isVisible({{ $fiche->id }})"
    :style="'order: ' + sortedIds.indexOf({{ $fiche->id }})"
    x-cloak
>
    <x-fiche-icon :fiche="$fiche" class="fiche-list-icon" />
    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
        <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
        <span class="text-xs text-[var(--color-text-secondary)]">
            {{ $fiche->user?->full_name }}@if($fiche->user?->organisation), {{ $fiche->user->organisation }}@endif
        </span>
    </div>
    <span class="fiche-list-kudos {{ $fiche->kudos_count > 0 ? 'fiche-list-kudos-active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
        {{ $fiche->kudos_count }}
    </span>
</a>
```

Key changes:
- Replaced inline SVG `<span class="fiche-list-icon">` with `<x-fiche-icon :fiche="$fiche" class="fiche-list-icon" />`
- Changed title from `text-base` to `text-lg` (18px)
- Added `gap-4` between icon and text via the existing flex container (handled by CSS `.fiche-list-item` gap)

- [ ] **Step 2: Build and verify**

```bash
npm run build
```

- [ ] **Step 3: Take a screenshot to verify visually**

```bash
node scripts/screenshot.cjs /initiatieven/1 /tmp/fiche-icons.png
```

Review the screenshot to confirm the colored icon discs render correctly. All fiches will show the fallback document icon until the backfill command runs — that's expected.

- [ ] **Step 4: Commit**

```bash
git add resources/views/initiatives/show.blade.php
git commit -m "feat: use fiche-icon component in initiative show view"
```

---

## Chunk 4: Backfill Command and Final Testing

### Task 10: Create the backfill artisan command

**Files:**
- Create: `app/Console/Commands/AssignFicheIcons.php`
- Test: `tests/Feature/Commands/AssignFicheIconsTest.php`

- [ ] **Step 1: Create the command**

```bash
php artisan make:command AssignFicheIcons --no-interaction
```

- [ ] **Step 2: Write the command**

In `app/Console/Commands/AssignFicheIcons.php`:

```php
<?php

namespace App\Console\Commands;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Console\Command;

class AssignFicheIcons extends Command
{
    protected $signature = 'fiches:assign-icons {--force : Re-assign icons even for fiches that already have one}';

    protected $description = 'Assign Lucide icons to fiches using AI';

    public function handle(): int
    {
        $query = Fiche::query();

        if (! $this->option('force')) {
            $query->whereNull('icon');
        }

        $fiches = $query->get();

        if ($fiches->isEmpty()) {
            $this->info('No fiches to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$fiches->count()} fiches...");
        $bar = $this->output->createProgressBar($fiches->count());
        $bar->start();

        foreach ($fiches as $fiche) {
            AssignFicheIcon::dispatch($fiche);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! Icons will be assigned as jobs are processed.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 3: Write the test**

```bash
php artisan make:test Commands/AssignFicheIconsTest --phpunit --no-interaction
```

In `tests/Feature/Commands/AssignFicheIconsTest.php`:

```php
<?php

namespace Tests\Feature\Commands;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AssignFicheIconsTest extends TestCase
{
    use RefreshDatabase;

    public function testProcessesFichesWithNullIcon(): void
    {
        Queue::fake();

        Fiche::factory()->count(3)->create(['icon' => null]);

        $this->artisan('fiches:assign-icons')
            ->expectsOutputToContain('Processing 3 fiches')
            ->assertExitCode(0);

        // 3 from backfill + 3 from observer on create = 6 total
        Queue::assertPushed(AssignFicheIcon::class, 6);
    }

    public function testSkipsFichesWithExistingIcon(): void
    {
        Queue::fake();

        Fiche::factory()->withIcon('music')->create();
        Fiche::factory()->create(['icon' => null]);

        $this->artisan('fiches:assign-icons')
            ->expectsOutputToContain('Processing 1 fiches')
            ->assertExitCode(0);
    }

    public function testForceReassignsAllFiches(): void
    {
        Queue::fake();

        Fiche::factory()->withIcon('music')->count(2)->create();

        $this->artisan('fiches:assign-icons', ['--force' => true])
            ->expectsOutputToContain('Processing 2 fiches')
            ->assertExitCode(0);

        // 2 from backfill + 2 from observer on create = 4 total
        Queue::assertPushed(AssignFicheIcon::class, 4);
    }

    public function testHandlesEmptyDatabase(): void
    {
        Queue::fake();

        $this->artisan('fiches:assign-icons')
            ->expectsOutputToContain('No fiches to process')
            ->assertExitCode(0);
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=AssignFicheIcons
```

Expected: 4 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/AssignFicheIcons.php tests/Feature/Commands/AssignFicheIconsTest.php
git commit -m "feat: add fiches:assign-icons backfill command"
```

---

### Task 11: Run Pint and full test suite

- [ ] **Step 1: Run Pint on all changed files**

```bash
vendor/bin/pint --dirty --format agent
```

Fix any formatting issues.

- [ ] **Step 2: Run full test suite**

```bash
php artisan test --compact
```

All tests should pass.

- [ ] **Step 3: Commit formatting fixes (if any)**

```bash
git add -A
git commit -m "style: apply Pint formatting"
```

---

### Task 12: Run backfill on existing fiches

This is a manual post-deployment step, not automated in tests.

- [ ] **Step 1: Ensure ANTHROPIC_API_KEY is set in `.env`**

Check that the `.env` file has `ANTHROPIC_API_KEY=sk-ant-...` set.

- [ ] **Step 2: Run the backfill command**

```bash
php artisan fiches:assign-icons
```

- [ ] **Step 3: Process the queued jobs**

```bash
php artisan queue:work --stop-when-empty
```

- [ ] **Step 4: Verify icons were assigned**

```bash
php artisan tinker --execute="echo App\Models\Fiche::whereNotNull('icon')->count() . ' of ' . App\Models\Fiche::count() . ' fiches have icons'"
```

- [ ] **Step 5: Take a screenshot to verify**

```bash
node scripts/screenshot.cjs /initiatieven/1 /tmp/fiche-icons-final.png
```

Review the screenshot — fiches should now show contextual icons in colored discs.
