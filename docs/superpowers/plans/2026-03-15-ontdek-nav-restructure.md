# Ontdek Nav Restructure Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move Favorieten, Downloads, and Mijn fiches out of the profile sidebar into a new "Ontdek" dropdown in the main navigation.

**Architecture:** Create two new controllers (`DownloadsAndBookmarksController`, `MyFichesController`) to host the moved logic. Add public routes at `/favorieten` and `/mijn-fiches` with anonymous CTA fallbacks. Convert the "Initiatieven" nav link into an "Ontdek" dropdown matching existing dropdown patterns. Strip the profile sidebar down to account-only items.

**Tech Stack:** Laravel 12, Blade, Livewire 4, Flux UI Pro, Tailwind CSS v4, Alpine.js

**Spec:** `docs/superpowers/specs/2026-03-15-ontdek-nav-restructure-design.md`

---

## File Structure

| File | Action | Responsibility |
|------|--------|----------------|
| `routes/web.php` | Modify | Add new public routes, remove old profile routes, add 301 redirects |
| `app/Http/Controllers/DownloadsAndBookmarksController.php` | Create | Handle `/favorieten` — downloads + bookmarks data |
| `app/Http/Controllers/MyFichesController.php` | Create | Handle `/mijn-fiches` — user's contributed fiches |
| `app/Http/Controllers/ProfileController.php` | Modify | Remove `bookmarks()` and `fiches()` methods |
| `resources/views/downloads-and-bookmarks.blade.php` | Create | Two-column layout for downloads + favorieten |
| `resources/views/my-fiches.blade.php` | Create | Mijn fiches page (moved from profile) |
| `resources/views/components/nav.blade.php` | Modify | Ontdek dropdown (desktop + mobile) |
| `resources/views/components/sidebar-layout.blade.php` | Modify | Remove moved items, remove `$newFicheCommentsCount` |
| `resources/views/components/layout.blade.php` | Modify | Update footer link from `profile.bookmarks` → `bookmarks.index` |
| `resources/views/livewire/fiche-kudos.blade.php` | Modify | Update `profile.bookmarks` → `bookmarks.index` |
| `tests/Feature/DownloadsAndBookmarksTest.php` | Create | Tests for `/favorieten` page |
| `tests/Feature/MyFichesTest.php` | Create | Tests for `/mijn-fiches` page |
| `tests/Feature/ProfileDownloadsTest.php` | Delete | Dead code — references never-implemented route |
| `tests/Feature/ProfileFichesTest.php` | Delete | Replaced by `MyFichesTest.php` |
| `tests/Feature/InitiativeTest.php` | Modify | Update `profile.bookmarks` reference |
| `resources/views/profile/bookmarks.blade.php` | Delete | Replaced by `downloads-and-bookmarks.blade.php` |
| `resources/views/profile/downloads.blade.php` | Delete | Replaced by `downloads-and-bookmarks.blade.php` |
| `resources/views/profile/fiches.blade.php` | Delete | Replaced by `my-fiches.blade.php` |

---

## Chunk 1: Routes, Controllers, and Tests

### Task 1: Routes and Redirects

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing test for new routes**

Create `tests/Feature/DownloadsAndBookmarksTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadsAndBookmarksTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsCanAccessFavorietenPage(): void
    {
        $response = $this->get('/favorieten');

        $response->assertOk();
    }

    public function testAuthenticatedUsersCanAccessFavorietenPage(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/favorieten');

        $response->assertOk();
    }
}
```

Run: `php artisan test --compact --filter=testGuestsCanAccessFavorietenPage`
Expected: FAIL (route not found)

- [ ] **Step 2: Write failing test for mijn-fiches route**

Create `tests/Feature/MyFichesTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyFichesTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsCanAccessMijnFichesPage(): void
    {
        $response = $this->get('/mijn-fiches');

        $response->assertOk();
    }

    public function testAuthenticatedUsersCanAccessMijnFichesPage(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/mijn-fiches');

        $response->assertOk();
    }
}
```

Run: `php artisan test --compact --filter=testGuestsCanAccessMijnFichesPage`
Expected: FAIL (route not found)

- [ ] **Step 3: Write failing redirect tests**

Add to `tests/Feature/DownloadsAndBookmarksTest.php`:

```php
public function testOldProfileFavorietenRedirectsToNewUrl(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profiel/favorieten');

    $response->assertRedirect('/favorieten');
    $response->assertStatus(301);
}
```

Add to `tests/Feature/MyFichesTest.php`:

```php
public function testOldProfileFichesRedirectsToNewUrl(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profiel/fiches');

    $response->assertRedirect('/mijn-fiches');
    $response->assertStatus(301);
}
```

Run: `php artisan test --compact --filter=testOldProfileFavorietenRedirectsToNewUrl`
Expected: FAIL

- [ ] **Step 4: Create stub controllers**

Create `app/Http/Controllers/DownloadsAndBookmarksController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DownloadsAndBookmarksController extends Controller
{
    public function __invoke(): View
    {
        return view('downloads-and-bookmarks');
    }
}
```

Create `app/Http/Controllers/MyFichesController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MyFichesController extends Controller
{
    public function __invoke(): View
    {
        return view('my-fiches');
    }
}
```

Create stub views:

`resources/views/downloads-and-bookmarks.blade.php`:
```blade
<x-layout title="Downloads & favorieten">
    <div class="py-8">
        <h1>Downloads & favorieten</h1>
    </div>
</x-layout>
```

`resources/views/my-fiches.blade.php`:
```blade
<x-layout title="Mijn fiches">
    <div class="py-8">
        <h1>Mijn fiches</h1>
    </div>
</x-layout>
```

- [ ] **Step 5: Add new routes and redirects to `routes/web.php`**

Add these **outside** the auth middleware group (they are public):

```php
use App\Http\Controllers\DownloadsAndBookmarksController;
use App\Http\Controllers\MyFichesController;

// Ontdek section (public — anonymous users see conversion CTA)
Route::get('/favorieten', DownloadsAndBookmarksController::class)->name('bookmarks.index');
Route::get('/mijn-fiches', MyFichesController::class)->name('my-fiches.index');
```

Inside the auth middleware group, replace the two old routes:

```php
// Remove these lines:
Route::get('/profiel/favorieten', [HvProfileController::class, 'bookmarks'])->name('profile.bookmarks');
Route::get('/profiel/fiches', [HvProfileController::class, 'fiches'])->name('profile.fiches');

// Add 301 redirects that KEEP the old route names (so views using route('profile.bookmarks')
// and route('profile.fiches') still resolve until all references are updated in later tasks):
Route::redirect('/profiel/favorieten', '/favorieten', 301)->name('profile.bookmarks');
Route::redirect('/profiel/fiches', '/mijn-fiches', 301)->name('profile.fiches');
```

- [ ] **Step 6: Run tests to verify routes work**

Run: `php artisan test --compact tests/Feature/DownloadsAndBookmarksTest.php tests/Feature/MyFichesTest.php`
Expected: PASS (all 6 tests)

- [ ] **Step 7: Commit**

```bash
git add routes/web.php app/Http/Controllers/DownloadsAndBookmarksController.php app/Http/Controllers/MyFichesController.php resources/views/downloads-and-bookmarks.blade.php resources/views/my-fiches.blade.php tests/Feature/DownloadsAndBookmarksTest.php tests/Feature/MyFichesTest.php
git commit -m "feat: add routes for /favorieten and /mijn-fiches with redirects from old profile URLs"
```

### Task 2: DownloadsAndBookmarksController — Full Implementation

**Files:**
- Modify: `app/Http/Controllers/DownloadsAndBookmarksController.php`

- [ ] **Step 1: Write tests for authenticated behavior**

Add to `tests/Feature/DownloadsAndBookmarksTest.php`:

```php
use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\Like;
use App\Models\UserInteraction;

public function testShowsDownloadedFiches(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->create();
    $fiche = Fiche::factory()->for($initiative)->for($user, 'user')->create(['published' => true]);

    UserInteraction::create([
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => $fiche->id,
        'type' => 'download',
    ]);

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertOk();
    $response->assertSee($fiche->title);
}

public function testShowsBookmarkedFiches(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->create();
    $fiche = Fiche::factory()->for($initiative)->for($user, 'user')->create(['published' => true]);

    Like::create([
        'user_id' => $user->id,
        'likeable_type' => Fiche::class,
        'likeable_id' => $fiche->id,
        'type' => 'bookmark',
    ]);

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertOk();
    $response->assertSee($fiche->title);
}

public function testFiltersOutOrphanedDownloads(): void
{
    $user = User::factory()->create();

    // Create an interaction pointing to a non-existent fiche
    UserInteraction::create([
        'user_id' => $user->id,
        'interactable_type' => Fiche::class,
        'interactable_id' => 99999,
        'type' => 'download',
    ]);

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertOk();
    // Should not crash — orphaned records are filtered out
}

public function testGuestSeesConversionCta(): void
{
    $response = $this->get(route('bookmarks.index'));

    $response->assertOk();
    $response->assertSee('Bewaar je favoriete fiches');
    $response->assertSee('Maak een gratis account');
}

public function testShowsEmptyStatesWhenNoItems(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertOk();
    $response->assertSee('Je hebt nog geen fiches gedownload');
    $response->assertSee('Je hebt nog geen fiches als favoriet opgeslagen');
}
```

Run: `php artisan test --compact --filter=testShowsDownloadedFiches`
Expected: FAIL

- [ ] **Step 2: Implement controller logic**

Update `app/Http/Controllers/DownloadsAndBookmarksController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Fiche;
use App\Models\UserInteraction;
use Illuminate\View\View;

class DownloadsAndBookmarksController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if (! $user) {
            return view('downloads-and-bookmarks', [
                'downloads' => collect(),
                'bookmarks' => collect(),
                'isGuest' => true,
            ]);
        }

        $downloads = UserInteraction::where('user_id', $user->id)
            ->where('type', 'download')
            ->where('interactable_type', Fiche::class)
            ->whereHas('interactable')
            ->with('interactable.initiative', 'interactable.user')
            ->latest()
            ->get()
            ->pluck('interactable');

        $bookmarks = $user->bookmarks()
            ->whereHas('likeable')
            ->with('likeable.initiative', 'likeable.user')
            ->latest()
            ->get()
            ->pluck('likeable');

        return view('downloads-and-bookmarks', [
            'downloads' => $downloads,
            'bookmarks' => $bookmarks,
            'isGuest' => false,
        ]);
    }
}
```

- [ ] **Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/DownloadsAndBookmarksTest.php`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/DownloadsAndBookmarksController.php tests/Feature/DownloadsAndBookmarksTest.php
git commit -m "feat: implement DownloadsAndBookmarksController with downloads and bookmarks queries"
```

### Task 3: MyFichesController — Full Implementation

**Files:**
- Modify: `app/Http/Controllers/MyFichesController.php`

- [ ] **Step 1: Write tests for authenticated behavior**

Add to `tests/Feature/MyFichesTest.php`:

```php
use App\Models\Comment;
use App\Models\Fiche;
use App\Models\Initiative;

public function testShowsUserFichesWithStats(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->create();
    $fiche = Fiche::factory()->for($initiative)->for($user, 'user')->create(['published' => true]);

    $response = $this->actingAs($user)->get(route('my-fiches.index'));

    $response->assertOk();
    $response->assertSee($fiche->title);
}

public function testShowsNewCommentsAlert(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->create();
    $fiche = Fiche::factory()->for($initiative)->for($user, 'user')->create(['published' => true]);
    $commenter = User::factory()->create();
    Comment::factory()->for($fiche, 'commentable')->for($commenter)->create();

    $response = $this->actingAs($user)->get(route('my-fiches.index'));

    $response->assertOk();
    $response->assertSee('nieuwe');
}

public function testUpdatesCommentsSeenTimestamp(): void
{
    $user = User::factory()->create();
    $this->assertNull($user->fiches_comments_seen_at);

    $this->actingAs($user)->get(route('my-fiches.index'));

    $user->refresh();
    $this->assertNotNull($user->fiches_comments_seen_at);
}

public function testGuestSeesConversionCta(): void
{
    $response = $this->get(route('my-fiches.index'));

    $response->assertOk();
    $response->assertSee('Deel jouw ervaring');
    $response->assertSee('Maak een gratis account');
}

public function testShowsEmptyStateForAuthenticatedUser(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('my-fiches.index'));

    $response->assertOk();
    $response->assertSee('Je hebt nog geen fiches geschreven');
}
```

Run: `php artisan test --compact --filter=testShowsUserFichesWithStats`
Expected: FAIL

- [ ] **Step 2: Implement controller logic**

Move the logic from `ProfileController::fiches()` to `MyFichesController`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Fiche;
use Illuminate\View\View;

class MyFichesController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        if (! $user) {
            return view('my-fiches', [
                'fiches' => collect(),
                'stats' => null,
                'newCommentsCount' => 0,
                'isGuest' => true,
            ]);
        }

        $fiches = $user->fiches()
            ->with('initiative')
            ->withCount([
                'comments',
                'files',
                'likes as bookmarks_count' => fn ($q) => $q->where('type', 'bookmark'),
            ])
            ->latest()
            ->get();

        $newCommentsCount = Comment::whereHasMorph('commentable', Fiche::class, fn ($q) => $q->where('user_id', $user->id))
            ->when($user->fiches_comments_seen_at, fn ($q) => $q->where('comments.created_at', '>', $user->fiches_comments_seen_at))
            ->count();

        $stats = [
            'total' => $fiches->count(),
            'published' => $fiches->where('published', true)->count(),
            'drafts' => $fiches->where('published', false)->count(),
            'downloads' => $fiches->sum('download_count'),
            'kudos' => $fiches->sum('kudos_count'),
            'comments' => $fiches->sum('comments_count'),
        ];

        $user->update(['fiches_comments_seen_at' => now()]);

        return view('my-fiches', compact('fiches', 'stats', 'newCommentsCount') + ['isGuest' => false]);
    }
}
```

- [ ] **Step 3: Run tests**

Run: `php artisan test --compact tests/Feature/MyFichesTest.php`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/MyFichesController.php tests/Feature/MyFichesTest.php
git commit -m "feat: implement MyFichesController with fiches logic moved from ProfileController"
```

### Task 4: Clean up ProfileController and Old Tests

**Files:**
- Modify: `app/Http/Controllers/ProfileController.php`
- Delete: `tests/Feature/ProfileDownloadsTest.php`
- Delete: `tests/Feature/ProfileFichesTest.php`
- Modify: `tests/Feature/InitiativeTest.php`

- [ ] **Step 1: Remove `bookmarks()` and `fiches()` from ProfileController**

Delete the `bookmarks()` method (lines 119-132) and the `fiches()` method (lines 87-117) from `app/Http/Controllers/ProfileController.php`.

Also remove unused imports: `Comment`, `Fiche`.

- [ ] **Step 2: Delete old test files**

```bash
rm tests/Feature/ProfileDownloadsTest.php tests/Feature/ProfileFichesTest.php
```

- [ ] **Step 3: Update `tests/Feature/InitiativeTest.php`**

Find line 377 referencing `route('profile.bookmarks')` and change to `route('bookmarks.index')`.

- [ ] **Step 4: Run tests to verify nothing is broken**

Run: `php artisan test --compact tests/Feature/InitiativeTest.php tests/Feature/DownloadsAndBookmarksTest.php tests/Feature/MyFichesTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ProfileController.php tests/Feature/InitiativeTest.php
git rm tests/Feature/ProfileDownloadsTest.php tests/Feature/ProfileFichesTest.php
git commit -m "refactor: remove moved methods from ProfileController, delete superseded tests"
```

---

## Chunk 2: Views and Navigation

### Task 5: Downloads & Favorieten View

**Files:**
- Modify: `resources/views/downloads-and-bookmarks.blade.php`

Refer to:
- `@tailwindcss-development` skill for Tailwind classes
- Existing fiche list pattern in `resources/views/profile/bookmarks.blade.php` (the `.fiche-list-item` / `.fiche-list-icon` classes)
- Spec section 3 for layout details

- [ ] **Step 1: Build the full view**

Replace the stub `resources/views/downloads-and-bookmarks.blade.php` with the full implementation:

```blade
<x-layout title="Downloads & favorieten" bg-class="bg-[var(--color-bg-cream)]">
    <div class="py-8 sm:py-12">
        @if($isGuest)
            {{-- Anonymous conversion CTA --}}
            <div class="max-w-lg mx-auto text-center py-16">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <flux:icon name="arrow-down-tray" class="size-12 text-[var(--color-border-light)]" />
                    <flux:icon name="bookmark" class="size-12 text-[var(--color-border-light)]" />
                </div>
                <h1 class="text-[var(--text-h2)] mb-3">Bewaar je favoriete fiches</h1>
                <p class="text-[var(--color-text-secondary)] font-light mb-8">
                    Sla inspirerende fiches op als favoriet en download ze om later te gebruiken. Zo heb je altijd ideeën bij de hand.
                </p>
                <flux:button variant="primary" href="{{ route('register') }}">Maak een gratis account</flux:button>
                <p class="mt-4 text-sm text-[var(--color-text-secondary)]">
                    Al een account? <a href="{{ route('login') }}" class="cta-link">Log in</a>
                </p>
            </div>
        @else
            {{-- Page header --}}
            <div class="mb-8">
                <p class="section-label mb-1">Ontdek</p>
                <h1 class="text-[var(--text-h2)]">Downloads & favorieten</h1>
                <p class="text-[var(--color-text-secondary)] font-light mt-2">Fiches die je hebt gedownload of als favoriet opgeslagen.</p>
            </div>

            {{-- Two-column layout --}}
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                {{-- Downloads column (wider) --}}
                <div class="lg:flex-[3]">
                    <p class="section-label mb-3 flex items-center gap-2">
                        <flux:icon name="arrow-down-tray" variant="mini" class="size-4" />
                        Downloads
                        <span class="bg-[var(--color-bg-subtle)] rounded-full px-2 py-0.5 text-xs font-normal">{{ $downloads->count() }}</span>
                    </p>

                    @if($downloads->isEmpty())
                        <div class="text-center py-12">
                            <flux:icon name="arrow-down-tray" class="size-12 mx-auto text-[var(--color-border-light)] mb-3" />
                            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches gedownload.</flux:text>
                            <flux:button variant="ghost" href="{{ route('initiatives.index') }}">Ontdek initiatieven</flux:button>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($downloads as $fiche)
                                <div class="fiche-list-item group">
                                    <div class="fiche-list-icon">
                                        <flux:icon name="arrow-down-tray" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-2">
                                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors line-clamp-2">{{ $fiche->title }}</a>
                                            @if($fiche->has_diamond)
                                                <x-diamond-badge class="shrink-0 mt-0.5" />
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-[var(--color-text-secondary)] truncate">
                                            @if($fiche->initiative)
                                                <span>{{ $fiche->initiative->title }}</span>
                                                <span class="text-[var(--color-border-light)]">&middot;</span>
                                            @endif
                                            @if($fiche->user)
                                                <span>{{ $fiche->user->first_name }} {{ $fiche->user->last_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}">
                                        <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-[var(--color-border-hover)] group-hover:text-[var(--color-primary)] transition-colors" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Favorieten column (narrower) --}}
                <div class="lg:flex-[2]">
                    <p class="section-label mb-3 flex items-center gap-2">
                        <flux:icon name="bookmark" variant="mini" class="size-4" />
                        Favorieten
                        <span class="bg-[var(--color-bg-subtle)] rounded-full px-2 py-0.5 text-xs font-normal">{{ $bookmarks->count() }}</span>
                    </p>

                    @if($bookmarks->isEmpty())
                        <div class="text-center py-12">
                            <flux:icon name="bookmark" class="size-12 mx-auto text-[var(--color-border-light)] mb-3" />
                            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches als favoriet opgeslagen.</flux:text>
                            <flux:button variant="ghost" href="{{ route('initiatives.index') }}">Ontdek initiatieven</flux:button>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($bookmarks as $fiche)
                                <div class="fiche-list-item group">
                                    <div class="fiche-list-icon">
                                        <flux:icon name="bookmark" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start gap-2">
                                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors line-clamp-2">{{ $fiche->title }}</a>
                                            @if($fiche->has_diamond)
                                                <x-diamond-badge class="shrink-0 mt-0.5" />
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-[var(--color-text-secondary)] truncate">
                                            @if($fiche->initiative)
                                                <span>{{ $fiche->initiative->title }}</span>
                                                <span class="text-[var(--color-border-light)]">&middot;</span>
                                            @endif
                                            @if($fiche->user)
                                                <span>{{ $fiche->user->first_name }} {{ $fiche->user->last_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}">
                                        <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-[var(--color-border-hover)] group-hover:text-[var(--color-primary)] transition-colors" />
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-layout>
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --compact tests/Feature/DownloadsAndBookmarksTest.php`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add resources/views/downloads-and-bookmarks.blade.php
git commit -m "feat: build Downloads & Favorieten two-column view"
```

### Task 6: Mijn Fiches View

**Files:**
- Modify: `resources/views/my-fiches.blade.php`

Refer to: existing `resources/views/profile/fiches.blade.php` for the fiche list pattern and stats strip.

- [ ] **Step 1: Build the full view**

Replace the stub `resources/views/my-fiches.blade.php`. This is the profile/fiches view adapted to standalone layout with guest CTA:

```blade
<x-layout title="Mijn fiches" bg-class="bg-[var(--color-bg-cream)]">
    <div class="py-8 sm:py-12">
        @if($isGuest)
            {{-- Anonymous conversion CTA --}}
            <div class="max-w-lg mx-auto text-center py-16">
                <flux:icon name="document-text" class="size-16 mx-auto text-[var(--color-border-light)] mb-6" />
                <h1 class="text-[var(--text-h2)] mb-3">Deel jouw ervaring met collega's</h1>
                <p class="text-[var(--color-text-secondary)] font-light mb-8">
                    Schrijf een fiche en help andere animatoren met praktische ideeën.
                </p>
                <flux:button variant="primary" href="{{ route('register') }}">Maak een gratis account</flux:button>
                <p class="mt-4 text-sm text-[var(--color-text-secondary)]">
                    Al een account? <a href="{{ route('login') }}" class="cta-link">Log in</a>
                </p>
            </div>
        @else
            {{-- Page header --}}
            <div class="mb-8">
                <p class="section-label mb-1">Ontdek</p>
                <div class="flex items-baseline justify-between gap-4">
                    <h1 class="text-[var(--text-h2)]">Mijn fiches</h1>
                    @if($fiches->isNotEmpty())
                        <flux:button variant="primary" size="sm" icon="plus" href="{{ route('fiches.create') }}">Nieuwe fiche</flux:button>
                    @endif
                </div>
                <p class="text-[var(--color-text-secondary)] font-light mt-2">Bekijk en beheer je fiches en hun statistieken.</p>
            </div>

            @if($newCommentsCount > 0)
                <div class="bg-[var(--color-bg-accent-light)] border border-[var(--color-border-light)] rounded-lg p-4 mb-6 flex items-center gap-3">
                    <flux:icon name="chat-bubble-oval-left-ellipsis" variant="mini" class="size-5 text-[var(--color-primary)] shrink-0" />
                    <span class="text-sm text-[var(--color-text-primary)]">
                        Je hebt <strong>{{ $newCommentsCount }}</strong> nieuwe {{ $newCommentsCount === 1 ? 'reactie' : 'reacties' }} op je fiches.
                    </span>
                </div>
            @endif

            @if($fiches->isNotEmpty())
                {{-- Stats strip --}}
                <div class="text-sm text-[var(--color-text-secondary)] mb-6">
                    <p class="mb-1">
                        <strong class="text-[var(--color-text-primary)]">{{ $stats['total'] }}</strong> fiches
                        <span class="text-xs">({{ $stats['published'] }} gepubliceerd, {{ $stats['drafts'] }} {{ Str::plural('concept', $stats['drafts']) }})</span>
                    </p>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span class="flex items-center gap-1">
                            <flux:icon name="arrow-down-tray" variant="mini" class="size-4" />
                            <strong class="text-[var(--color-text-primary)]">{{ $stats['downloads'] }}</strong> downloads
                        </span>
                        <span class="flex items-center gap-1">
                            <flux:icon name="heart" variant="mini" class="size-4" />
                            <strong class="text-[var(--color-text-primary)]">{{ $stats['kudos'] }}</strong> kudos
                        </span>
                        <span class="flex items-center gap-1">
                            <flux:icon name="chat-bubble-oval-left-ellipsis" variant="mini" class="size-4" />
                            <strong class="text-[var(--color-text-primary)]">{{ $stats['comments'] }}</strong> reacties
                        </span>
                    </div>
                </div>

                {{-- Fiche list --}}
                <div class="space-y-2">
                    @foreach($fiches as $fiche)
                        <div class="fiche-list-item group">
                            <div class="fiche-list-icon">
                                <flux:icon :name="$fiche->published ? 'document-text' : 'pencil-square'" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors truncate">{{ $fiche->title }}</a>
                                    @if($fiche->has_diamond)
                                        <x-diamond-badge class="shrink-0" />
                                    @endif
                                    @if(!$fiche->published)
                                        <flux:badge size="sm" color="yellow" inset="top bottom" class="shrink-0">Concept</flux:badge>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs text-[var(--color-text-secondary)]">
                                    @if($fiche->initiative)
                                        <span class="truncate">{{ $fiche->initiative->title }}</span>
                                        <span class="text-[var(--color-border-light)]">&middot;</span>
                                    @endif
                                    <span class="whitespace-nowrap">{{ $fiche->created_at->format('d-m-Y') }}</span>
                                </div>
                            </div>
                            <div class="hidden sm:flex items-center gap-3 text-xs text-[var(--color-text-secondary)] shrink-0">
                                <span class="flex items-center gap-1" title="Downloads">
                                    <flux:icon name="arrow-down-tray" variant="micro" class="size-3.5" />
                                    {{ $fiche->download_count }}
                                </span>
                                <span class="flex items-center gap-1" title="Kudos">
                                    <flux:icon name="heart" variant="micro" class="size-3.5" />
                                    {{ $fiche->kudos_count }}
                                </span>
                                <span class="flex items-center gap-1" title="Reacties">
                                    <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" class="size-3.5" />
                                    {{ $fiche->comments_count }}
                                </span>
                            </div>
                            <flux:button variant="ghost" href="{{ route('fiches.edit', $fiche) }}" icon="pencil-square" class="shrink-0" />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <flux:icon name="document-text" class="size-16 mx-auto text-[var(--color-border-light)] mb-4" />
                    <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches geschreven.</flux:text>
                    <flux:button variant="primary" href="{{ route('fiches.create') }}">
                        Schrijf je eerste fiche
                    </flux:button>
                </div>
            @endif
        @endif
    </div>
</x-layout>
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --compact tests/Feature/MyFichesTest.php`
Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add resources/views/my-fiches.blade.php
git commit -m "feat: build Mijn Fiches standalone view with guest CTA"
```

### Task 7: Update Main Navigation — Ontdek Dropdown

**Files:**
- Modify: `resources/views/components/nav.blade.php`

Refer to: the existing "Tools & inspiratie" dropdown pattern in the same file (lines 71-128) for the exact markup structure.

- [ ] **Step 1: Replace desktop Initiatieven link with Ontdek dropdown**

In `resources/views/components/nav.blade.php`, replace the plain `<a>` link for Initiatieven (lines 16-21) with a dropdown matching the "Tools & inspiratie" pattern:

```blade
<div x-data="{ open: false, timeout: null }" @mouseenter="clearTimeout(timeout); open = true" @mouseleave="timeout = setTimeout(() => open = false, 150)" class="relative">
    <button @click="open = !open" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] rounded-lg hover:bg-[var(--color-bg-accent-light)] transition-colors whitespace-nowrap">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
        </svg>
        Ontdek
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.outside="open = false" class="absolute left-0 top-full mt-1 w-96 bg-white rounded-xl shadow-lg border border-[var(--color-border-light)] z-50">
        <div class="px-4 pt-3 pb-1">
            <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-secondary)]">Ontdek</span>
        </div>

        <div class="divide-y divide-[var(--color-border-light)]">
            <a href="{{ route('initiatives.index') }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                <span class="shrink-0 w-7 flex items-center justify-center text-[var(--color-primary)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>
                <div class="flex-1 min-w-0">
                    <span class="font-semibold text-sm text-[var(--color-text-primary)]">Initiatieven</span>
                    <p class="text-xs text-[var(--color-text-secondary)]">Ontdek alle activiteiten en ideeën</p>
                </div>
                <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
            </a>

            <a href="{{ route('bookmarks.index') }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                <span class="shrink-0 w-7 flex items-center justify-center text-[var(--color-primary)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </span>
                <div class="flex-1 min-w-0">
                    <span class="font-semibold text-sm text-[var(--color-text-primary)]">Downloads & favorieten</span>
                    <p class="text-xs text-[var(--color-text-secondary)]">Gedownloade en opgeslagen fiches</p>
                </div>
                <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
            </a>

            <a href="{{ route('my-fiches.index') }}" class="flex items-center gap-3 w-full px-4 py-3 hover:bg-[var(--color-bg-cream)] transition-colors">
                <span class="shrink-0 w-7 flex items-center justify-center text-[var(--color-primary)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </span>
                <div class="flex-1 min-w-0">
                    <span class="font-semibold text-sm text-[var(--color-text-primary)]">Mijn fiches</span>
                    <p class="text-xs text-[var(--color-text-secondary)]">Fiches die je hebt bijgedragen</p>
                </div>
                <span class="shrink-0 text-[var(--color-text-secondary)] text-sm">&rarr;</span>
            </a>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Replace mobile Initiatieven link with expandable Ontdek section**

In the mobile navigation section (around line 200-207), replace the plain `<a>` for Initiatieven with an expandable section matching the "Doelen" mobile pattern (uses `border-t` top border, consistent with other mobile dropdowns):

```blade
<div x-data="{ open: false }">
    <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-lg text-base font-medium text-[var(--color-text-primary)] hover:text-[var(--color-primary)] hover:bg-[var(--color-bg-accent-light)]">
        <span class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
            Ontdek
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>
    <div x-cloak x-show="open" x-transition class="mt-1 space-y-1 pl-3">
        <a href="{{ route('initiatives.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--color-bg-cream)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
            </svg>
            <span class="text-sm font-medium text-[var(--color-text-primary)]">Initiatieven</span>
        </a>
        <a href="{{ route('bookmarks.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--color-bg-cream)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <span class="text-sm font-medium text-[var(--color-text-primary)]">Downloads & favorieten</span>
        </a>
        <a href="{{ route('my-fiches.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-[var(--color-bg-cream)]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <span class="text-sm font-medium text-[var(--color-text-primary)]">Mijn fiches</span>
        </a>
    </div>
</div>
```

- [ ] **Step 3: Remove Favorieten and Fiches from avatar dropdown**

In the `<flux:menu>` section (around lines 156-160), remove:

```blade
<flux:menu.item href="{{ route('profile.bookmarks') }}" icon="bookmark">Favorieten</flux:menu.item>
<flux:menu.item href="{{ route('profile.fiches') }}" icon="document-text">Fiches</flux:menu.item>
```

- [ ] **Step 4: Build assets and visually verify**

Run: `npm run build`
Then take a screenshot: `node scripts/screenshot.cjs / /tmp/nav-ontdek.png`
Visually verify the nav looks correct.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/nav.blade.php
git commit -m "feat: convert Initiatieven nav to Ontdek dropdown, remove moved items from avatar menu"
```

### Task 8: Update Profile Sidebar

**Files:**
- Modify: `resources/views/components/sidebar-layout.blade.php`

- [ ] **Step 1: Remove `$newFicheCommentsCount` query**

Delete line 3:
```php
@php($newFicheCommentsCount = auth()->user()->newFicheCommentsCount())
```

- [ ] **Step 2: Remove moved items from mobile horizontal tabs**

Remove the `<a>` tags for Favorieten (around line 25-28) and Fiches with badge (around line 29-35) from the mobile tab bar. Note: there is no Downloads item in the mobile sidebar — it was never implemented.

- [ ] **Step 3: Remove moved items from desktop navlist**

Remove these lines from the `<flux:navlist.group heading="Profiel">` section:

```blade
<flux:navlist.item href="{{ route('profile.bookmarks') }}" icon="bookmark" :current="request()->routeIs('profile.bookmarks')">Favorieten</flux:navlist.item>
<flux:navlist.item href="{{ route('profile.downloads') }}" icon="arrow-down-tray" :current="request()->routeIs('profile.downloads')">Downloads</flux:navlist.item>
<flux:navlist.item href="{{ route('profile.fiches') }}" icon="document-text" :current="request()->routeIs('profile.fiches')" :badge="$newFicheCommentsCount > 0 ? $newFicheCommentsCount : null">Fiches</flux:navlist.item>
```

Note: The Downloads navlist item may or may not exist depending on whether the canceled downloads plan was partially implemented. If it's not there, just remove Favorieten and Fiches.

- [ ] **Step 4: Run existing profile tests**

Run: `php artisan test --compact --filter=testProfilePage`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/sidebar-layout.blade.php
git commit -m "refactor: strip profile sidebar to account-only items"
```

### Task 9: Update Remaining Route References

**Files:**
- Modify: `resources/views/components/layout.blade.php`
- Modify: `resources/views/livewire/fiche-kudos.blade.php`

- [ ] **Step 1: Update footer link in layout.blade.php**

In `resources/views/components/layout.blade.php` around line 135, change:

```blade
<li><a href="{{ route('profile.bookmarks') }}" ...>Mijn favorieten</a></li>
```
to:
```blade
<li><a href="{{ route('bookmarks.index') }}" ...>Mijn favorieten</a></li>
```

- [ ] **Step 2: Update fiche-kudos bookmark link**

In `resources/views/livewire/fiche-kudos.blade.php` around line 163, change:

```blade
<a href="{{ route('profile.bookmarks') }}" ...>
```
to:
```blade
<a href="{{ route('bookmarks.index') }}" ...>
```

- [ ] **Step 3: Remove old route names from redirects**

In `routes/web.php`, the redirects were given old route names to prevent breakage during the transition. Now that all references are updated, remove the `->name()` calls:

```php
// Change from:
Route::redirect('/profiel/favorieten', '/favorieten', 301)->name('profile.bookmarks');
Route::redirect('/profiel/fiches', '/mijn-fiches', 301)->name('profile.fiches');

// To:
Route::redirect('/profiel/favorieten', '/favorieten', 301);
Route::redirect('/profiel/fiches', '/mijn-fiches', 301);
```

- [ ] **Step 4: Delete old view files**

```bash
rm resources/views/profile/bookmarks.blade.php resources/views/profile/downloads.blade.php resources/views/profile/fiches.blade.php
```

- [ ] **Step 5: Run full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "chore: update remaining route references, remove old route names, delete old profile views"
```

### Task 10: Visual Verification

- [ ] **Step 1: Build assets**

Run: `npm run build`

- [ ] **Step 2: Take screenshots of key pages**

```bash
node scripts/screenshot.cjs / /tmp/home-nav.png
node scripts/screenshot.cjs /favorieten /tmp/favorieten.png
node scripts/screenshot.cjs /mijn-fiches /tmp/mijn-fiches.png
node scripts/screenshot.cjs /profiel /tmp/profiel-sidebar.png
```

Visually verify:
- Ontdek dropdown appears in main nav with 3 items
- Downloads & favorieten page shows two-column layout
- Mijn fiches page shows fiche list with stats
- Profile sidebar only shows Persoonlijke info + Beveiliging

- [ ] **Step 3: Take anonymous screenshots**

Log out (or use incognito). Take screenshots of `/favorieten` and `/mijn-fiches` to verify the CTA pages render correctly.

- [ ] **Step 4: Take mobile screenshots**

```bash
node scripts/screenshot.cjs / /tmp/home-nav-mobile.png --mobile
node scripts/screenshot.cjs /favorieten /tmp/favorieten-mobile.png --mobile
```

Verify the mobile nav has the Ontdek expandable section and the two-column layout stacks properly.
