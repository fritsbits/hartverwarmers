# Per-User Fiche Interaction Tracking — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Track which fiches each logged-in user has viewed and downloaded, and show subtle visual indicators (muted styling for viewed, download icon for downloaded) everywhere fiches appear.

**Architecture:** A polymorphic `user_interactions` table stores one record per user+fiche+type combination. A `FicheInteractionService` provides a single batch query per page load. Controllers pass interaction data to views, which apply CSS classes and icons.

**Tech Stack:** Laravel 12, PHPUnit 11, Tailwind CSS v4, Flux UI Pro v2

**Spec:** `docs/superpowers/specs/2026-03-14-fiche-interaction-tracking-design.md`

---

## Chunk 1: Migration, Model & Service

### Task 1: Create the `user_interactions` migration and model

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_user_interactions_table.php` (via artisan)
- Create: `app/Models/UserInteraction.php` (via artisan)
- Test: `tests/Feature/UserInteractionTest.php` (via artisan)

- [ ] **Step 1: Write the failing test — interaction creation**

Create `tests/Feature/UserInteractionTest.php` via artisan:

```bash
php artisan make:test UserInteractionTest --phpunit --no-interaction
```

Add the first test:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_view_interaction(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        $interaction = UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $this->assertDatabaseHas('user_interactions', [
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);
        $this->assertNotNull($interaction->created_at);
    }

    public function test_unique_constraint_prevents_duplicate_interactions(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);
    }

    public function test_same_user_can_have_view_and_download_for_same_fiche(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);

        $this->assertDatabaseCount('user_interactions', 2);
    }

    public function test_interactions_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $user->forceDelete();

        $this->assertDatabaseCount('user_interactions', 0);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=UserInteractionTest`
Expected: FAIL — table and model don't exist

- [ ] **Step 3: Create migration via artisan**

```bash
php artisan make:migration create_user_interactions_table --no-interaction
```

Edit the generated migration:

```php
public function up(): void
{
    Schema::create('user_interactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->morphs('interactable');
        $table->string('type');
        $table->timestamp('created_at')->useCurrent();

        $table->unique(['user_id', 'interactable_type', 'interactable_id', 'type'], 'user_interactions_unique');
        $table->index(['user_id', 'interactable_type', 'type']);
    });
}

public function down(): void
{
    Schema::dropIfExists('user_interactions');
}
```

- [ ] **Step 4: Create model via artisan**

```bash
php artisan make:model UserInteraction --no-interaction
```

Replace contents with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserInteraction extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'interactable_type', 'interactable_id', 'type'];

    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=UserInteractionTest`
Expected: PASS (all 4 tests)

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add app/Models/UserInteraction.php database/migrations/*_create_user_interactions_table.php tests/Feature/UserInteractionTest.php
git commit -m "feat: add user_interactions table and model for fiche view/download tracking"
```

### Task 2: Create the `FicheInteractionService`

**Files:**
- Create: `app/Services/FicheInteractionService.php`
- Test: `tests/Feature/UserInteractionTest.php` (add tests)

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/UserInteractionTest.php`:

```php
public function test_service_returns_interaction_types_for_user(): void
{
    $user = User::factory()->create();
    $fiche1 = Fiche::factory()->published()->create();
    $fiche2 = Fiche::factory()->published()->create();

    UserInteraction::create([
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche1->id,
        'type' => 'view',
    ]);
    UserInteraction::create([
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche1->id,
        'type' => 'download',
    ]);
    UserInteraction::create([
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche2->id,
        'type' => 'view',
    ]);

    $service = new \App\Services\FicheInteractionService();
    $result = $service->forUser($user, [$fiche1->id, $fiche2->id]);

    $this->assertCount(2, $result);
    $this->assertContains('view', $result[$fiche1->id]);
    $this->assertContains('download', $result[$fiche1->id]);
    $this->assertContains('view', $result[$fiche2->id]);
    $this->assertNotContains('download', $result[$fiche2->id]);
}

public function test_service_returns_empty_array_for_guest(): void
{
    $service = new \App\Services\FicheInteractionService();
    $result = $service->forUser(null, [1, 2, 3]);

    $this->assertEmpty($result);
}

public function test_service_returns_empty_array_for_empty_fiche_ids(): void
{
    $user = User::factory()->create();
    $service = new \App\Services\FicheInteractionService();
    $result = $service->forUser($user, []);

    $this->assertEmpty($result);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=test_service_returns`
Expected: FAIL — class not found

- [ ] **Step 3: Create the service**

Create `app/Services/FicheInteractionService.php`:

```php
<?php

namespace App\Services;

use App\Models\Fiche;
use App\Models\User;
use App\Models\UserInteraction;

class FicheInteractionService
{
    /**
     * Returns a map of fiche ID → array of interaction types for the given user.
     *
     * @param  array<int>|Collection  $ficheIds
     * @return array<int, array<string>>
     */
    public function forUser(?User $user, $ficheIds): array
    {
        if (! $user || empty($ficheIds)) {
            return [];
        }

        return UserInteraction::where('user_id', $user->id)
            ->where('interactable_type', Fiche::class)
            ->whereIn('interactable_id', $ficheIds)
            ->get()
            ->groupBy('interactable_id')
            ->map(fn ($interactions) => $interactions->pluck('type')->all())
            ->all();
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=test_service_returns`
Expected: PASS (all 3)

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Services/FicheInteractionService.php tests/Feature/UserInteractionTest.php
git commit -m "feat: add FicheInteractionService for batch interaction queries"
```

---

## Chunk 2: Recording Interactions

### Task 3: Record view interactions in FicheController@show

**Files:**
- Modify: `app/Http/Controllers/FicheController.php` (show method, around line 30)
- Test: `tests/Feature/UserInteractionTest.php` (add tests)

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/UserInteractionTest.php`:

```php
public function test_viewing_fiche_page_creates_view_interaction(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

    $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));

    $this->assertDatabaseHas('user_interactions', [
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche->id,
        'type' => 'view',
    ]);
}

public function test_viewing_fiche_page_does_not_duplicate_view_interaction(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

    $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));
    $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));

    $this->assertDatabaseCount('user_interactions', 1);
}

public function test_guest_viewing_fiche_page_does_not_create_interaction(): void
{
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

    $this->get(route('fiches.show', [$initiative, $fiche]));

    $this->assertDatabaseCount('user_interactions', 0);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=test_viewing_fiche_page`
Expected: FAIL (first two — no interaction recorded)

The guest test may already pass since nothing writes without auth. Run it anyway.

- [ ] **Step 3: Add view tracking to FicheController@show**

In `app/Http/Controllers/FicheController.php`, add `use App\Models\UserInteraction;` at the top.

In the `show()` method, after the existing code that loads the fiche data (after line 45, before the return statement), add:

```php
if (auth()->check()) {
    UserInteraction::firstOrCreate([
        'user_id' => auth()->id(),
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche->id,
        'type' => 'view',
    ]);
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=test_viewing_fiche_page`
Expected: PASS (all 3)

Also: `php artisan test --compact --filter=test_guest_viewing`
Expected: PASS

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/FicheController.php tests/Feature/UserInteractionTest.php
git commit -m "feat: record view interaction when logged-in user opens fiche page"
```

### Task 4: Record download interactions in FicheController@downloadFiles

**Files:**
- Modify: `app/Http/Controllers/FicheController.php` (downloadFiles method, around line 57)
- Test: `tests/Feature/UserInteractionTest.php` (add tests)

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/UserInteractionTest.php`:

```php
public function test_downloading_fiche_creates_download_interaction(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('files/test-file.pdf', 'test content');

    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
    \App\Models\File::factory()->create(['fiche_id' => $fiche->id, 'path' => 'files/test-file.pdf']);

    $this->actingAs($user)->get(route('fiches.download', [$initiative, $fiche]));

    $this->assertDatabaseHas('user_interactions', [
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche->id,
        'type' => 'download',
    ]);
}

public function test_downloading_fiche_also_increments_global_download_count(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('files/test-file.pdf', 'test content');

    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id, 'download_count' => 0]);
    \App\Models\File::factory()->create(['fiche_id' => $fiche->id, 'path' => 'files/test-file.pdf']);

    $this->actingAs($user)->get(route('fiches.download', [$initiative, $fiche]));

    $this->assertEquals(1, $fiche->fresh()->download_count);
}

public function test_guest_downloading_fiche_does_not_create_interaction(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('files/test-file.pdf', 'test content');

    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
    \App\Models\File::factory()->create(['fiche_id' => $fiche->id, 'path' => 'files/test-file.pdf']);

    $response = $this->get(route('fiches.download', [$initiative, $fiche]));
    $response->assertStatus(200);

    $this->assertDatabaseMissing('user_interactions', [
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche->id,
        'type' => 'download',
    ]);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=test_downloading_fiche`
Expected: FAIL (first test — no download interaction recorded)

- [ ] **Step 3: Add download tracking to FicheController@downloadFiles**

In `app/Http/Controllers/FicheController.php`, in the `downloadFiles()` method, after the existing `$fiche->increment('download_count')` line (line 69), add:

```php
if (auth()->check()) {
    UserInteraction::firstOrCreate([
        'user_id' => auth()->id(),
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche->id,
        'type' => 'download',
    ]);
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=test_downloading_fiche`
Expected: PASS (all 3)

Note: The download tests use `Storage::fake('public')` — add `use Illuminate\Support\Facades\Storage;` to the test file imports.

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/FicheController.php tests/Feature/UserInteractionTest.php
git commit -m "feat: record download interaction when logged-in user downloads fiche files"
```

---

## Chunk 3: Visual Indicators — CSS & View Integration

### Task 5: Add CSS classes for viewed/downloaded states

**Files:**
- Modify: `resources/css/app.css` (after the existing fiche-list styles, around line 751)

- [ ] **Step 1: Add the CSS classes**

In `resources/css/app.css`, after the existing `.fiche-list-expand` block (around line 751), add:

```css
/* Viewed fiche: muted appearance */
.fiche-list-item-viewed {
    opacity: 0.55;
}

.fiche-list-item-viewed:hover {
    opacity: 0.85;
}

/* Downloaded indicator icon */
.fiche-list-downloaded {
    display: inline-flex;
    align-items: center;
    color: var(--color-text-secondary);
    opacity: 0.6;
}

.fiche-list-downloaded svg {
    width: 14px;
    height: 14px;
}
```

- [ ] **Step 2: Build frontend**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: add CSS classes for viewed and downloaded fiche states"
```

### Task 6: Integrate interaction data into InitiativeController@show

**Files:**
- Modify: `app/Http/Controllers/InitiativeController.php` (show method)
- Test: `tests/Feature/UserInteractionTest.php` (add tests)

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/UserInteractionTest.php`:

```php
public function test_initiative_show_passes_interaction_data_for_logged_in_user(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

    UserInteraction::create([
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche->id,
        'type' => 'view',
    ]);

    $response = $this->actingAs($user)->get(route('initiatives.show', $initiative));

    $response->assertStatus(200);
    $response->assertViewHas('ficheInteractions');
    $interactions = $response->viewData('ficheInteractions');
    $this->assertArrayHasKey($fiche->id, $interactions);
    $this->assertContains('view', $interactions[$fiche->id]);
}

public function test_initiative_show_passes_empty_interactions_for_guest(): void
{
    $initiative = Initiative::factory()->published()->create();
    Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

    $response = $this->get(route('initiatives.show', $initiative));

    $response->assertStatus(200);
    $response->assertViewHas('ficheInteractions', []);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=test_initiative_show_passes`
Expected: FAIL — `ficheInteractions` not in view data

- [ ] **Step 3: Add interaction loading to InitiativeController@show**

In `app/Http/Controllers/InitiativeController.php`, add at the top:

```php
use App\Services\FicheInteractionService;
```

In the `show()` method, after the `$randomOrder` line (around line 195), add:

```php
$ficheInteractions = app(FicheInteractionService::class)
    ->forUser(auth()->user(), $initiative->fiches->pluck('id'));
```

Add `'ficheInteractions' => $ficheInteractions` to the `return view(...)` call.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=test_initiative_show_passes`
Expected: PASS (both)

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/InitiativeController.php tests/Feature/UserInteractionTest.php
git commit -m "feat: pass fiche interaction data to initiative show view"
```

### Task 7: Apply visual indicators to the initiative show fiche list

**Files:**
- Modify: `resources/views/initiatives/show.blade.php` (fiche list items, around lines 186-212)

- [ ] **Step 1: Update fiche list items to show viewed/downloaded state**

In `resources/views/initiatives/show.blade.php`, find the `@foreach($initiative->fiches as $fiche)` loop (around line 186). Update the `<a>` element to apply the viewed class and add the downloaded icon.

Replace the opening `<a` tag:

```blade
@php
    $viewed = isset($ficheInteractions[$fiche->id]) && in_array('view', $ficheInteractions[$fiche->id]);
    $downloaded = isset($ficheInteractions[$fiche->id]) && in_array('download', $ficheInteractions[$fiche->id]);
@endphp
<a
    href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}"
    class="fiche-list-item {{ $viewed ? 'fiche-list-item-viewed' : '' }}"
    x-show="isVisible({{ $fiche->id }})"
    :style="'order: ' + sortedIds.indexOf({{ $fiche->id }})"
    x-cloak
>
```

Then, after the kudos `<span>` and before the closing `</a>`, add the downloaded icon:

```blade
@if($downloaded)
    <span class="fiche-list-downloaded" title="Gedownload">
        <flux:icon name="arrow-down-tray" class="size-3.5" />
    </span>
@endif
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --compact tests/Feature/InitiativeTest.php`
Expected: All pass

- [ ] **Step 3: Build frontend and screenshot**

Run: `npm run build`
Run: `node scripts/screenshot.cjs /initiatieven/quiz /tmp/quiz-interactions.png`
Verify: If you have viewed/downloaded fiches, they should appear muted with download icons.

- [ ] **Step 4: Commit**

```bash
git add resources/views/initiatives/show.blade.php
git commit -m "feat: show viewed/downloaded indicators on initiative fiche list"
```

---

## Chunk 4: Integration Across Other Pages

### Task 8: Add interaction data to FicheController@show (other fiches sidebar)

**Files:**
- Modify: `app/Http/Controllers/FicheController.php` (show method)
- Modify: `resources/views/fiches/show.blade.php`
- Test: `tests/Feature/UserInteractionTest.php` (add test)

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/UserInteractionTest.php`:

```php
public function test_fiche_show_passes_interaction_data_for_logged_in_user(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);
    $otherFiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

    UserInteraction::create([
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $otherFiche->id,
        'type' => 'view',
    ]);

    $response = $this->actingAs($user)->get(route('fiches.show', [$initiative, $fiche]));

    $response->assertStatus(200);
    $response->assertViewHas('ficheInteractions');
    $interactions = $response->viewData('ficheInteractions');
    $this->assertArrayHasKey($otherFiche->id, $interactions);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=test_fiche_show_passes_interaction`
Expected: FAIL

- [ ] **Step 3: Add interaction loading to FicheController@show**

In `FicheController@show()`, add `use App\Services\FicheInteractionService;` at the top if not already present.

Before the return statement, add:

```php
$ficheInteractions = app(FicheInteractionService::class)
    ->forUser(auth()->user(), $otherFiches->pluck('id'));
```

Add `'ficheInteractions' => $ficheInteractions` to the return view call.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=test_fiche_show_passes_interaction`
Expected: PASS

- [ ] **Step 5: Update the fiche show view's "Meer fiches" section**

In `resources/views/fiches/show.blade.php`, find the `@foreach($otherFiches as $other)` loop. Note: the loop variable is `$other`, not `$fiche`. Apply the viewed/downloaded pattern:

```blade
@php
    $viewed = isset($ficheInteractions[$other->id]) && in_array('view', $ficheInteractions[$other->id]);
    $downloaded = isset($ficheInteractions[$other->id]) && in_array('download', $ficheInteractions[$other->id]);
@endphp
```

Apply the `fiche-list-item-viewed` class and download icon as in Task 7.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/FicheController.php resources/views/fiches/show.blade.php tests/Feature/UserInteractionTest.php
git commit -m "feat: show interaction indicators on fiche detail 'Meer fiches' sidebar"
```

### Task 9: Add interaction data to remaining controllers

**Files:**
- Modify: `app/Http/Controllers/ContributorController.php` (show method)
- Modify: `app/Http/Controllers/ProfileController.php` (fiches and bookmarks methods)

For each controller:

- [ ] **Step 1: Add interaction loading to ContributorController@show**

Add `use App\Services\FicheInteractionService;` and load interactions for the displayed fiches:

```php
$ficheInteractions = app(FicheInteractionService::class)
    ->forUser(auth()->user(), $user->fiches->pluck('id'));
```

Pass `'ficheInteractions' => $ficheInteractions` to the view.

- [ ] **Step 2: Add interaction loading to ProfileController@fiches**

Same pattern — load interactions for the user's own fiches and pass to view.

- [ ] **Step 3: Add interaction loading to ProfileController@bookmarks**

Same pattern — load interactions for bookmarked fiches and pass to view.

- [ ] **Step 4: Update the contributor show view**

In `resources/views/contributors/show.blade.php`, find where fiches are listed. Apply the same `$viewed`/`$downloaded` pattern using inline `@php` blocks and the `fiche-list-item-viewed` CSS class + download icon, same as Task 7.

- [ ] **Step 5: Update the profile fiches and bookmarks views**

In `resources/views/profile/fiches.blade.php` and `resources/views/profile/bookmarks.blade.php`, apply the same viewed/downloaded pattern. These views use `.fiche-list-item` inline markup (not `<x-fiche-card>`), so the same approach as Task 7 applies.

Note: The Livewire search component is **deferred** — it renders fiches differently and would need separate handling.

- [ ] **Step 6: Run all tests**

Run: `php artisan test --compact`
Expected: All pass (minus pre-existing failures)

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/ContributorController.php app/Http/Controllers/ProfileController.php resources/views/components/fiche-card.blade.php
git commit -m "feat: show interaction indicators on contributor, profile, and bookmark pages"
```

---

## Chunk 5: Final Verification

### Task 10: Run full test suite and visual verification

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: All pass (minus pre-existing failures)

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Screenshot initiative detail page**

First, view a few fiches to create interactions:
```bash
# The screenshot script logs in as admin — view some fiches first via the browser,
# or create interactions via tinker:
php artisan tinker --execute="App\Models\UserInteraction::firstOrCreate(['user_id' => 1, 'interactable_type' => App\Models\Fiche::class, 'interactable_id' => 1, 'type' => 'view']); App\Models\UserInteraction::firstOrCreate(['user_id' => 1, 'interactable_type' => App\Models\Fiche::class, 'interactable_id' => 1, 'type' => 'download']); App\Models\UserInteraction::firstOrCreate(['user_id' => 1, 'interactable_type' => App\Models\Fiche::class, 'interactable_id' => 2, 'type' => 'view']);"
```

Run: `node scripts/screenshot.cjs /initiatieven/quiz /tmp/quiz-interactions-final.png`
Verify: Some fiches appear muted (viewed), some have download icon, unvisited ones are full opacity.

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "fix: final polish for fiche interaction indicators"
```
