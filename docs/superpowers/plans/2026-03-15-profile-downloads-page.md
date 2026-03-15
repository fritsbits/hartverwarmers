# Profile Downloads Page — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Downloads" page to the profile section showing fiches the user has downloaded, accessible from the sidebar below "Favorieten".

**Architecture:** New route, controller method, and Blade view following the exact pattern of the existing bookmarks/favorieten page. Uses the `user_interactions` table (type=download) to query downloaded fiches.

**Tech Stack:** Laravel 12, Blade, Flux UI Pro v2

---

### Task 1: Add route, controller method, sidebar nav item, and view

**Files:**
- Modify: `routes/web.php` — add route
- Modify: `app/Http/Controllers/ProfileController.php` — add `downloads()` method
- Create: `resources/views/profile/downloads.blade.php` — new view
- Modify: `resources/views/components/sidebar-layout.blade.php` — add nav item
- Test: `tests/Feature/ProfileDownloadsTest.php` — new test file

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ProfileDownloadsTest.php` via `php artisan make:test ProfileDownloadsTest --phpunit --no-interaction`:

```php
<?php

namespace Tests\Feature;

use App\Models\Fiche;
use App\Models\Initiative;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDownloadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_downloads_page_requires_authentication(): void
    {
        $response = $this->get(route('profile.downloads'));

        $response->assertRedirect(route('login'));
    }

    public function test_downloads_page_shows_downloaded_fiches(): void
    {
        $user = User::factory()->create();
        $initiative = Initiative::factory()->published()->create();
        $fiche = Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'download',
        ]);

        $response = $this->actingAs($user)->get(route('profile.downloads'));

        $response->assertStatus(200);
        $response->assertSee($fiche->title);
    }

    public function test_downloads_page_does_not_show_only_viewed_fiches(): void
    {
        $user = User::factory()->create();
        $fiche = Fiche::factory()->published()->create();

        UserInteraction::create([
            'user_id' => $user->id,
            'interactable_type' => Fiche::class,
            'interactable_id' => $fiche->id,
            'type' => 'view',
        ]);

        $response = $this->actingAs($user)->get(route('profile.downloads'));

        $response->assertStatus(200);
        $response->assertDontSee($fiche->title);
    }

    public function test_downloads_page_shows_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.downloads'));

        $response->assertStatus(200);
        $response->assertSee('Nog geen downloads');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=ProfileDownloadsTest`
Expected: FAIL — route not defined

- [ ] **Step 3: Add the route**

In `routes/web.php`, find the profile routes group (around line 50, near `profile.bookmarks`). Add after the bookmarks route:

```php
Route::get('/profiel/downloads', [HvProfileController::class, 'downloads'])->name('profile.downloads');
```

- [ ] **Step 4: Add the controller method**

In `app/Http/Controllers/ProfileController.php`, add the `downloads()` method (follow the pattern of the `bookmarks()` method):

```php
public function downloads(Request $request): View
{
    $fiches = Fiche::query()
        ->whereIn('id', UserInteraction::where('user_id', $request->user()->id)
            ->where('interactable_type', Fiche::class)
            ->where('type', 'download')
            ->pluck('interactable_id'))
        ->published()
        ->with(['initiative', 'user', 'files'])
        ->latest()
        ->get();

    return view('profile.downloads', [
        'fiches' => $fiches,
    ]);
}
```

Add `use App\Models\UserInteraction;` at the top if not already imported. Also add `use App\Models\Fiche;` if not present.

- [ ] **Step 5: Create the view**

Create `resources/views/profile/downloads.blade.php`, modeled after `bookmarks.blade.php`:

```blade
<x-sidebar-layout title="Downloads" section-label="Profiel" description="Fiches die je hebt gedownload.">
    @if($fiches->isEmpty())
        <div class="text-center py-16">
            <flux:icon.arrow-down-tray class="mx-auto mb-4 text-[var(--color-border-light)]" variant="outline" />
            <p class="text-[var(--color-text-secondary)] mb-4">Nog geen downloads</p>
            <flux:button href="{{ route('initiatives.index') }}" variant="outline" size="sm">Ontdek initiatieven</flux:button>
        </div>
    @else
        <p class="text-[var(--color-text-secondary)] mb-6">
            <strong class="text-[var(--color-text-primary)]">{{ $fiches->count() }}</strong> {{ $fiches->count() === 1 ? 'fiche' : 'fiches' }} gedownload
        </p>

        <div class="space-y-2">
            @foreach($fiches as $fiche)
                <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="fiche-list-item">
                    <x-fiche-icon :fiche="$fiche" class="fiche-list-icon" />
                    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                        <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
                        <span class="text-xs text-[var(--color-text-secondary)]">
                            {{ $fiche->initiative?->title }}
                            @if($fiche->user) &middot; {{ $fiche->user->full_name }}@endif
                        </span>
                    </div>
                    <span class="fiche-list-downloaded">
                        <flux:icon name="arrow-down-tray" class="size-3.5" />
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-[var(--color-border-hover)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @endforeach
        </div>
    @endif
</x-sidebar-layout>
```

Note: Check `resources/views/profile/bookmarks.blade.php` first. If `<x-fiche-icon>` is used there, use the same pattern. If not, use the pattern from the bookmarks page for the icon. The view file was recently modified — read it before writing to make sure you follow the current markup pattern exactly.

- [ ] **Step 6: Add sidebar nav item**

In `resources/views/components/sidebar-layout.blade.php`, find where "Favorieten" is listed in both the mobile nav and the desktop navlist. Add a "Downloads" item right below "Favorieten" in both locations.

For mobile (find the bookmarks tab button, add after it):
```blade
<a href="{{ route('profile.downloads') }}"
   class="..."
   ...>
    <flux:icon name="arrow-down-tray" class="size-5" />
    <span class="text-xs mt-1">Downloads</span>
</a>
```

For desktop (find the bookmarks navlist item, add after it):
```blade
<flux:navlist.item icon="arrow-down-tray" href="{{ route('profile.downloads') }}" :current="request()->routeIs('profile.downloads')">
    Downloads
</flux:navlist.item>
```

**IMPORTANT:** Read the sidebar-layout file first to match the exact markup pattern used for other nav items.

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --compact --filter=ProfileDownloadsTest`
Expected: PASS (all 4 tests)

- [ ] **Step 8: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 9: Build frontend and screenshot**

Run: `npm run build`
Run: `node scripts/screenshot.cjs /profiel/downloads /tmp/profile-downloads.png`
Verify: Downloads page shows with fiche list items

- [ ] **Step 10: Commit**

```bash
git add routes/web.php app/Http/Controllers/ProfileController.php resources/views/profile/downloads.blade.php resources/views/components/sidebar-layout.blade.php tests/Feature/ProfileDownloadsTest.php
git commit -m "feat: add downloads page to profile section"
```
