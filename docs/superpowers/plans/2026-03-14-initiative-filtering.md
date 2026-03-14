# Initiative & Fiche Filtering, Sorting & Search — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add search, sorting with URL param persistence, and icons/tooltips to the initiative detail page and initiatives index, so users don't lose their place when navigating back.

**Architecture:** Pure Alpine.js client-side filtering with `history.replaceState()` for URL param sync. No Livewire, no server-side filtering. All data loaded upfront in the controller and passed to Alpine via Blade `@js()` directives — consistent with the existing codebase pattern.

**Tech Stack:** Laravel 12, Alpine.js 3, Tailwind CSS v4, Flux UI Pro v2

**Spec:** `docs/superpowers/specs/2026-03-14-initiative-filtering-design.md`

---

## Chunk 1: Initiative Detail Page — Controller & Tests

### Task 1: Update InitiativeController@show to pass Alpine-compatible fiche data

**Files:**
- Modify: `app/Http/Controllers/InitiativeController.php` (the `show()` method, lines 145-202)

- [ ] **Step 1: Write the failing test — fiche data includes description and timestamp**

Add to `tests/Feature/InitiativeTest.php`:

```php
public function test_initiative_show_passes_fiche_alpine_data(): void
{
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create([
        'initiative_id' => $initiative->id,
        'description' => '<p>Een leuke quiz over dieren</p>',
        'kudos_count' => 5,
    ]);

    $response = $this->get(route('initiatives.show', $initiative));

    $response->assertStatus(200);
    $response->assertViewHas('ficheAlpineData');
    $ficheData = $response->viewData('ficheAlpineData');
    $this->assertCount(1, $ficheData);
    $this->assertEquals($fiche->id, $ficheData[0]['id']);
    $this->assertEquals($fiche->title, $ficheData[0]['title']);
    $this->assertStringNotContainsString('<p>', $ficheData[0]['description']);
    $this->assertEquals(5, $ficheData[0]['kudosCount']);
    $this->assertArrayHasKey('createdAt', $ficheData[0]);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=test_initiative_show_passes_fiche_alpine_data`
Expected: FAIL — `ficheAlpineData` view variable doesn't exist

- [ ] **Step 3: Write the failing test — random order array**

Add to `tests/Feature/InitiativeTest.php`:

```php
public function test_initiative_show_passes_random_order(): void
{
    $initiative = Initiative::factory()->published()->create();
    Fiche::factory()->published()->count(5)->create(['initiative_id' => $initiative->id]);

    $response = $this->get(route('initiatives.show', $initiative));

    $response->assertStatus(200);
    $response->assertViewHas('randomOrder');
    $randomOrder = $response->viewData('randomOrder');
    $this->assertIsArray($randomOrder);
    $this->assertCount(5, $randomOrder);
}
```

- [ ] **Step 4: Run test to verify it fails**

Run: `php artisan test --compact --filter=test_initiative_show_passes_random_order`
Expected: FAIL

- [ ] **Step 5: Implement controller changes**

In `InitiativeController@show()`, after the existing `$initiative->load(...)` call, add the Alpine data preparation:

```php
$ficheAlpineData = $initiative->fiches->map(fn ($fiche) => [
    'id' => $fiche->id,
    'title' => $fiche->title,
    'description' => Str::limit(strip_tags($fiche->description), 200),
    'kudosCount' => $fiche->kudos_count,
    'createdAt' => $fiche->created_at->timestamp,
])->values()->all();

$randomOrder = $initiative->fiches->pluck('id')->shuffle()->values()->all();
```

Add `$ficheAlpineData` and `$randomOrder` to the `return view(...)` call.

**Required:** Add `use Illuminate\Support\Str;` at the top of the controller — it is not currently imported.

- [ ] **Step 6: Run both tests to verify they pass**

Run: `php artisan test --compact --filter=test_initiative_show_passes_fiche`
Expected: PASS (both tests)

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/InitiativeController.php tests/Feature/InitiativeTest.php
git commit -m "feat: pass fiche Alpine data and random order to initiative show view"
```

### Task 2: Update existing tests that break due to removed expand button

**Files:**
- Modify: `tests/Feature/InitiativeTest.php`

The spec removes the "show 6 + expand" toggle and the diamond fiche pinning. Three existing tests will break:
- `test_initiative_show_displays_expand_button_when_more_than_six_fiches` (line 438)
- `test_initiative_show_hides_expand_button_when_six_or_fewer_fiches` (line 449)
- `test_initiative_show_highlights_diamond_fiche` (line 345) — asserts `Diamantje` text from `<x-fiche-card :show-diamond="true" />` which is removed

These tests validate the old UX. The new UX shows all fiches in a flat compact list, so these tests should be removed and replaced.

- [ ] **Step 1: Remove the three obsolete tests**

Delete `test_initiative_show_displays_expand_button_when_more_than_six_fiches`, `test_initiative_show_hides_expand_button_when_six_or_fewer_fiches`, and `test_initiative_show_highlights_diamond_fiche` from `tests/Feature/InitiativeTest.php`.

- [ ] **Step 2: Add replacement test — all fiches visible**

```php
public function test_initiative_show_displays_all_fiches_without_expand_toggle(): void
{
    $initiative = Initiative::factory()->published()->create();
    $fiches = Fiche::factory()->published()->count(10)->create(['initiative_id' => $initiative->id]);

    $response = $this->get(route('initiatives.show', $initiative));

    $response->assertStatus(200);
    foreach ($fiches as $fiche) {
        $response->assertSee($fiche->title);
    }
    $response->assertDontSee('meer</button>', false);
}
```

- [ ] **Step 3: Run the new test (it should pass already since the view changes come later — fiches are all present in HTML, just hidden by Alpine)**

Run: `php artisan test --compact --filter=test_initiative_show_displays_all_fiches_without_expand_toggle`
Expected: PASS (all fiches are in the HTML, the `x-show` just hides them client-side)

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/InitiativeTest.php
git commit -m "test: replace expand-button tests with all-fiches-visible test"
```

---

## Chunk 2: Initiative Detail Page — View Changes

### Task 3: Rewrite the fiche list section with search, sort, and URL params

**Files:**
- Modify: `resources/views/initiatives/show.blade.php` (lines 86-158, the left column fiche section)

This is the core view change. Replace the current diamond-fiche-pinning + expand/collapse pattern with a flat, searchable, sortable list driven by Alpine.js with URL param sync.

- [ ] **Step 1: Replace the fiche section in the show view**

In `resources/views/initiatives/show.blade.php`, replace everything inside `{{-- Left column: Fiches --}}` (from `<div class="lg:col-span-2">` through its closing `</div>`) with the new implementation.

The new Alpine `x-data` block wraps the entire left column:

```blade
<div class="lg:col-span-2" x-data="{
    search: new URLSearchParams(window.location.search).get('q') || '',
    sortMode: new URLSearchParams(window.location.search).get('sort') || 'newest',
    fiches: @js($ficheAlpineData),
    randomOrder: @js($randomOrder),
    updateUrl() {
        const params = new URLSearchParams();
        if (this.sortMode && this.sortMode !== 'newest') params.set('sort', this.sortMode);
        if (this.search) params.set('q', this.search);
        const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', url);
    },
    isVisible(id) {
        if (!this.search) return true;
        const item = this.fiches.find(f => f.id === id);
        if (!item) return false;
        const q = this.search.toLowerCase();
        return item.title.toLowerCase().includes(q) || item.description.toLowerCase().includes(q);
    },
    get sortedIds() {
        const visible = this.fiches.filter(f => this.isVisible(f.id));
        if (this.sortMode === 'random') {
            return this.randomOrder.filter(id => visible.some(f => f.id === id));
        }
        const sorted = [...visible];
        if (this.sortMode === 'popular') {
            sorted.sort((a, b) => b.kudosCount - a.kudosCount);
        } else if (this.sortMode === 'az') {
            sorted.sort((a, b) => a.title.localeCompare(b.title, 'nl'));
        } else {
            sorted.sort((a, b) => b.createdAt - a.createdAt);
        }
        return sorted.map(f => f.id);
    },
    get visibleCount() {
        return this.fiches.filter(f => this.isVisible(f.id)).length;
    }
}" x-init="$watch('search', () => updateUrl()); $watch('sortMode', () => updateUrl())">
```

- [ ] **Step 2: Add the section heading, search input, and sort pills**

Below the `x-data` opening, add:

```blade
<span class="section-label">Fiches</span>

@if($initiative->fiches->isEmpty())
    <h2 class="mt-1 mb-8">Fiches door collega's</h2>
    <p class="text-[var(--color-text-secondary)]">Nog geen fiches voor dit initiatief.</p>
@else
    <h2 class="mt-1 mb-8">{{ $initiative->fiches->count() }} {{ $initiative->fiches->count() === 1 ? 'uitwerking' : 'uitwerkingen' }} door collega's</h2>

    {{-- Toolbar: Search + Sort --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-6">
        <flux:input icon="magnifying-glass" placeholder="Zoek op titel of beschrijving..." x-model.debounce.200ms="search" class="sm:max-w-xs" />

        <div class="inline-flex rounded-full bg-[var(--color-bg-subtle)] p-1 sm:ml-auto flex-wrap">
            <button
                @click="sortMode = 'newest'"
                :class="sortMode === 'newest' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all"
                type="button"
                title="Nieuwste fiches eerst"
            >
                <flux:icon name="clock" class="size-4" />
                Nieuwste
            </button>
            <button
                @click="sortMode = 'popular'"
                :class="sortMode === 'popular' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all"
                type="button"
                title="Fiches met de meeste kudos"
            >
                <flux:icon name="heart" class="size-4" />
                Populair
            </button>
            <button
                @click="sortMode = 'random'"
                :class="sortMode === 'random' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all"
                type="button"
                title="Willekeurige volgorde — verras jezelf!"
            >
                <flux:icon name="arrows-right-left" class="size-4" />
                Willekeurig
            </button>
            <button
                @click="sortMode = 'az'"
                :class="sortMode === 'az' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all"
                type="button"
                title="Alfabetische volgorde"
            >
                <flux:icon name="bars-3-bottom-left" class="size-4" />
                A&ndash;Z
            </button>
        </div>
    </div>

    {{-- Fiche list (flex container required for CSS order to work) --}}
    <div class="flex flex-col gap-2">
        @foreach($initiative->fiches as $fiche)
            <a
                href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}"
                class="fiche-list-item"
                x-show="isVisible({{ $fiche->id }})"
                :style="'order: ' + sortedIds.indexOf({{ $fiche->id }})"
                x-cloak
            >
                <span class="fiche-list-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </span>
                <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                    <span class="font-body font-semibold text-base text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
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
        @endforeach
    </div>

    {{-- Empty search state --}}
    <div x-show="visibleCount === 0" x-cloak class="text-center py-12">
        <flux:icon.magnifying-glass class="mx-auto mb-4 text-[var(--color-border-light)]" variant="outline" />
        <p class="text-[var(--color-text-secondary)] mb-4">Geen fiches gevonden.</p>
        <flux:button variant="outline" size="sm" @click="search = ''">Wis zoekopdracht</flux:button>
    </div>
@endif
</div>
```

Key changes from original:
- No diamond fiche separation — all fiches in one flat list
- No `showAll` toggle — all fiches always visible (filtered by search)
- `x-show="isVisible(id)"` for search filtering
- `:style="'order: ' + sortedIds.indexOf(id)"` for CSS-based sort order (requires flex container, already set above)

- [ ] **Step 3: Run existing tests to verify nothing broke**

Run: `php artisan test --compact tests/Feature/InitiativeTest.php`
Expected: All tests pass (the removed expand-button tests were already deleted in Task 2)

- [ ] **Step 4: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Build frontend assets**

Run: `npm run build`

- [ ] **Step 6: Take a screenshot to verify the result**

Run: `node scripts/screenshot.cjs /initiatieven/quiz /tmp/quiz-after.png`
Verify: Search input visible, sort pills with icons visible, all fiches listed

- [ ] **Step 7: Commit**

```bash
git add resources/views/initiatives/show.blade.php
git commit -m "feat: add search, sort, and URL param sync to initiative detail page"
```

---

## Chunk 3: Initiatives Index Page — Controller & View Changes

### Task 4: Update InitiativeController@index — remove discoverOrder, add randomOrder

**Files:**
- Modify: `app/Http/Controllers/InitiativeController.php` (the `index()` method)
- Modify: `tests/Feature/InitiativeTest.php`

- [ ] **Step 1: Update the discover order test to test randomOrder instead**

In `tests/Feature/InitiativeTest.php`, replace `test_initiatives_index_provides_discover_order` with:

```php
public function test_initiatives_index_passes_random_order(): void
{
    Initiative::factory()->published()->count(5)->create();

    $response = $this->get(route('initiatives.index'));

    $response->assertStatus(200);
    $response->assertViewHas('randomOrder');
    $randomOrder = $response->viewData('randomOrder');
    $this->assertIsArray($randomOrder);
    $this->assertCount(5, $randomOrder);
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter=test_initiatives_index_passes_random_order`
Expected: FAIL — `randomOrder` not in view data

- [ ] **Step 3: Update the controller**

In `InitiativeController@index()`:

1. **Remove** the "Interleaved Ontdek order" block (lines 58-75): the `$rich`, `$growing`, `$needsLove` categorization and the `$discoverOrder` interleaving loop.

2. **Replace** with:
```php
// Random order for "Willekeurig" sort
$randomOrder = $initiatives->pluck('id')->shuffle()->values()->all();
```

3. **Keep** the `$needsLoveInitiatives` computation, but simplify it to use the `$initiatives` collection directly:
```php
$needsLoveInitiatives = $initiatives->filter(fn ($i) => $i->fiches_count < 3)
    ->sortByDesc('latest_fiche_at')
    ->take(3)
    ->map(fn ($i) => ['title' => $i->title, 'route' => route('initiatives.show', $i)])
    ->values()
    ->all();
```

4. **Update** the `return view(...)` call: replace `'discoverOrder' => $discoverOrder` with `'randomOrder' => $randomOrder`.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --compact --filter=test_initiatives_index_passes_random_order`
Expected: PASS

- [ ] **Step 5: Run all initiative tests**

Run: `php artisan test --compact tests/Feature/InitiativeTest.php`
Expected: All pass

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/InitiativeController.php tests/Feature/InitiativeTest.php
git commit -m "feat: replace discoverOrder with randomOrder on initiatives index"
```

### Task 5: Update the initiatives index view — new sort pills with icons, tooltips, and URL params

**Files:**
- Modify: `resources/views/initiatives/index.blade.php` (lines 2-53 Alpine data block, lines 200-223 sort pills)

- [ ] **Step 1: Update the Alpine x-data block**

Replace the Alpine `x-data` block at the top of the file. Key changes:
- Default `sortMode` reads from URL: `new URLSearchParams(window.location.search).get('sort') || 'az'`
- Default `search` reads from URL: `new URLSearchParams(window.location.search).get('q') || ''`
- Default `selectedGoals` reads from URL: parse `goals` param as comma-separated array
- Replace `discoverOrder` with `randomOrder: @js($randomOrder)`
- Add `updateUrl()` method that syncs all state to URL via `history.replaceState()`
- Update `sortedIds` getter: replace `discover` mode with `rich`, `needs-love`, `random` modes
- Add `$watch` calls in `x-init` to trigger `updateUrl()` on state changes

The full updated Alpine block:

```blade
<div x-data="{
    search: new URLSearchParams(window.location.search).get('q') || '',
    selectedGoals: (new URLSearchParams(window.location.search).get('goals') || '').split(',').filter(Boolean),
    sortMode: new URLSearchParams(window.location.search).get('sort') || 'az',
    randomOrder: @js($randomOrder),
    initiatives: @js($initiatives->map(fn ($i) => [
        'id' => $i->id,
        'title' => $i->title,
        'fichesCount' => $i->fiches_count,
        'goalSlugs' => $i->tags->pluck('slug')->values(),
    ])),
    updateUrl() {
        const params = new URLSearchParams();
        if (this.sortMode && this.sortMode !== 'az') params.set('sort', this.sortMode);
        if (this.search) params.set('q', this.search);
        if (this.selectedGoals.length > 0) params.set('goals', this.selectedGoals.join(','));
        const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', url);
    },
    toggleGoal(tagSlug) {
        const idx = this.selectedGoals.indexOf(tagSlug);
        if (idx === -1) {
            this.selectedGoals.push(tagSlug);
        } else {
            this.selectedGoals.splice(idx, 1);
        }
    },
    removeGoal(tagSlug) {
        this.selectedGoals = this.selectedGoals.filter(s => s !== tagSlug);
    },
    clearAll() {
        this.selectedGoals = [];
        this.search = '';
        this.sortMode = 'az';
    },
    isVisible(id) {
        const item = this.initiatives.find(i => i.id === id);
        if (!item) return false;
        if (this.search && !item.title.toLowerCase().includes(this.search.toLowerCase())) return false;
        if (this.selectedGoals.length > 0 && !this.selectedGoals.every(g => item.goalSlugs.includes(g))) return false;
        if (this.sortMode === 'rich' && item.fichesCount < 10) return false;
        if (this.sortMode === 'needs-love' && item.fichesCount >= 3) return false;
        return true;
    },
    get visibleCount() {
        return this.initiatives.filter(i => this.isVisible(i.id)).length;
    },
    get totalCount() {
        return this.initiatives.length;
    },
    get sortedIds() {
        if (this.sortMode === 'random') {
            return this.randomOrder;
        }
        const sorted = [...this.initiatives];
        if (this.sortMode === 'rich') {
            sorted.sort((a, b) => b.fichesCount - a.fichesCount);
        } else if (this.sortMode === 'needs-love') {
            sorted.sort((a, b) => a.fichesCount - b.fichesCount);
        } else {
            sorted.sort((a, b) => a.title.localeCompare(b.title, 'nl'));
        }
        return sorted.map(i => i.id);
    }
}" x-init="$watch('search', () => updateUrl()); $watch('sortMode', () => updateUrl()); $watch('selectedGoals', () => updateUrl())">
```

- [ ] **Step 2: Replace the sort pills in the toolbar**

Replace the sort pills `<div>` (the `inline-flex rounded-full` container with the 3 buttons) with the new 4-button version with icons and tooltips:

```blade
<div class="inline-flex rounded-full bg-[var(--color-bg-subtle)] p-1 sm:ml-auto flex-wrap">
    <button
        @click="sortMode = 'az'"
        :class="sortMode === 'az' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all"
        type="button"
        title="Alle initiatieven op alfabetische volgorde"
    >
        <flux:icon name="bars-3-bottom-left" class="size-4" />
        A&ndash;Z
    </button>
    <button
        @click="sortMode = 'rich'"
        :class="sortMode === 'rich' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all whitespace-nowrap"
        type="button"
        title="Initiatieven met de meeste uitwerkingen"
    >
        <flux:icon name="square-3-stack-3d" class="size-4" />
        Veel fiches
    </button>
    <button
        @click="sortMode = 'needs-love'"
        :class="sortMode === 'needs-love' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all whitespace-nowrap"
        type="button"
        title="Initiatieven die nog uitwerkingen zoeken"
    >
        <flux:icon name="hand-raised" class="size-4" />
        Hulp nodig
    </button>
    <button
        @click="sortMode = 'random'"
        :class="sortMode === 'random' ? 'bg-white shadow-sm text-[var(--color-text-primary)]' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all"
        type="button"
        title="Willekeurige volgorde — verras jezelf!"
    >
        <flux:icon name="arrows-right-left" class="size-4" />
        Willekeurig
    </button>
</div>
```

- [ ] **Step 3: Update the grid eager-loading line**

Replace:
```blade
@php $eagerIds = array_slice($discoverOrder, 0, 6); @endphp
```

With:
```blade
@php $eagerIds = $initiatives->take(6)->pluck('id')->all(); @endphp
```

- [ ] **Step 4: Update the "Jouw ervaring telt" card order**

The CTA card currently has `:style="'order: 9'"`. Change to `:style="'order: 999'"` so it always appears at the end regardless of sort.

- [ ] **Step 5: Run all initiative tests**

Run: `php artisan test --compact tests/Feature/InitiativeTest.php`
Expected: All pass

- [ ] **Step 6: Build frontend and screenshot**

Run: `npm run build`
Run: `node scripts/screenshot.cjs /initiatieven /tmp/initiatives-after.png`
Verify: New sort pills with icons visible, A–Z default selected

- [ ] **Step 7: Commit**

```bash
git add resources/views/initiatives/index.blade.php
git commit -m "feat: replace Ontdek sort with A-Z, Veel fiches, Hulp nodig, Willekeurig on initiatives index"
```

---

## Chunk 4: Polish & Final Verification

### Task 6: Run full test suite and fix any breakage

**Files:**
- Possibly: `tests/Feature/InitiativeTest.php`, controller, views

- [ ] **Step 1: Add URL param robustness test**

Add to `tests/Feature/InitiativeTest.php`:

```php
public function test_initiative_show_handles_invalid_url_params(): void
{
    $initiative = Initiative::factory()->published()->create();
    Fiche::factory()->published()->create(['initiative_id' => $initiative->id]);

    $response = $this->get(route('initiatives.show', $initiative) . '?sort=invalid&q=test');

    $response->assertStatus(200);
}
```

Run: `php artisan test --compact --filter=test_initiative_show_handles_invalid_url_params`
Expected: PASS (invalid sort falls through to default in Alpine, server doesn't validate)

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test --compact`
Expected: All tests pass

- [ ] **Step 3: Fix any failing tests**

If tests fail, investigate and fix. Common issues:
- Tests asserting `discoverOrder` view data → update to `randomOrder`
- Tests asserting expand button text → already removed in Task 2

- [ ] **Step 4: Run Pint on all modified files**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Commit any fixes**

```bash
git add -A
git commit -m "fix: resolve test failures from filtering changes"
```

### Task 7: Visual verification — both pages

- [ ] **Step 1: Screenshot initiative detail page (desktop)**

Run: `node scripts/screenshot.cjs /initiatieven/quiz /tmp/quiz-final.png`
Verify:
- All fiches visible (no "meer" button)
- Search input present
- Sort pills with icons: Nieuwste (active), Populair, Willekeurig, A–Z
- Tooltips on hover (can't verify in screenshot, but check HTML)

- [ ] **Step 2: Screenshot initiative detail page (mobile)**

Run: `node scripts/screenshot.cjs /initiatieven/quiz /tmp/quiz-final-mobile.png --mobile`
Verify: Sort pills wrap properly, search input full width

- [ ] **Step 3: Screenshot initiatives index (desktop)**

Run: `node scripts/screenshot.cjs /initiatieven /tmp/initiatives-final.png`
Verify:
- Sort pills with icons: A–Z (active), Veel fiches, Hulp nodig, Willekeurig
- Search input still present
- DIAMANT filter dropdown still present

- [ ] **Step 4: Screenshot initiatives index (mobile)**

Run: `node scripts/screenshot.cjs /initiatieven /tmp/initiatives-final-mobile.png --mobile`
Verify: Sort pills wrap, no horizontal overflow

- [ ] **Step 5: Test URL param persistence manually**

Open `https://26hartverwarmers.test/initiatieven/quiz?sort=popular` — verify Populair is selected.
Open `https://26hartverwarmers.test/initiatieven?sort=rich` — verify Veel fiches is selected and only 10+ fiche initiatives shown.

- [ ] **Step 6: Final commit if any visual fixes needed**

```bash
git add -A
git commit -m "fix: visual polish for initiative filtering"
```
