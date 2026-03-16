# Fiche Edit Button & Author-Aware Comment Copy — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a visible edit button on the fiche show page for authorized users, and show author-appropriate comment placeholder copy.

**Architecture:** Two small changes to existing views + one computed property on a Livewire component. No new files, routes, or migrations.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI Pro, PHPUnit

**Spec:** `docs/superpowers/specs/2026-03-16-fiche-edit-button-design.md`

---

## Chunk 1: Edit Button on Fiche Show Page

### Task 1: Add edit button tests to FicheShowTest

**Files:**
- Modify: `tests/Feature/FicheShowTest.php`

- [ ] **Step 1: Write tests for edit button visibility**

Add these tests to `tests/Feature/FicheShowTest.php`:

```php
public function test_author_sees_edit_button_on_own_fiche(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create([
        'initiative_id' => $initiative->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('fiches.show', [$initiative, $fiche]));

    $response->assertStatus(200);
    $response->assertSee('Bewerk');
    $response->assertSee(route('fiches.edit', $fiche));
}

public function test_curator_sees_edit_button_on_any_fiche(): void
{
    $curator = User::factory()->curator()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create([
        'initiative_id' => $initiative->id,
    ]);

    $response = $this->actingAs($curator)
        ->get(route('fiches.show', [$initiative, $fiche]));

    $response->assertStatus(200);
    $response->assertSee('Bewerk');
    $response->assertSee(route('fiches.edit', $fiche));
}

public function test_non_author_contributor_does_not_see_edit_button(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create([
        'initiative_id' => $initiative->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('fiches.show', [$initiative, $fiche]));

    $response->assertStatus(200);
    $response->assertDontSee(route('fiches.edit', $fiche));
}

public function test_admin_sees_edit_button_and_admin_dropdown(): void
{
    $admin = User::factory()->admin()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create([
        'initiative_id' => $initiative->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('fiches.show', [$initiative, $fiche]));

    $response->assertStatus(200);
    $response->assertSee('Bewerk');
    $response->assertSee(route('fiches.edit', $fiche));
    $response->assertSee('Admin');
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="test_author_sees_edit_button|test_curator_sees_edit_button|test_non_author_contributor|test_admin_sees_edit_button_and"`
Expected: The author/curator/admin tests FAIL (no edit button visible yet). The non-author test may pass already.

- [ ] **Step 3: Add edit button to show.blade.php**

In `resources/views/fiches/show.blade.php`, replace lines 55–105 (from after `</flux:breadcrumbs>` through the closing `</div>` of the container) with:

```blade
                <div class="flex items-center gap-2">
                    @can('update', $fiche)
                        <flux:button variant="ghost" size="sm" icon="pencil-square" href="{{ route('fiches.edit', $fiche) }}" class="border border-[var(--color-border-light)] text-xs text-[var(--color-text-secondary)]">Bewerk</flux:button>
                    @endcan

                    @auth
                    @if(auth()->user()->isAdmin())
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="cog-6-tooth" icon-trailing="chevron-down" class="text-xs text-[var(--color-text-secondary)]">
                                Admin
                            </flux:button>

                            <flux:menu>
                                <form action="{{ route('fiches.toggleDiamond', [$initiative, $fiche]) }}" method="POST">
                                    @csrf
                                    <flux:menu.item type="submit" icon="sparkles">
                                        {{ $fiche->has_diamond ? 'Diamantje verwijderen' : 'Diamantje toekennen' }}
                                    </flux:menu.item>
                                </form>

                                <flux:modal.trigger name="delete-fiche">
                                    <flux:menu.item variant="danger" icon="trash">Verwijder</flux:menu.item>
                                </flux:modal.trigger>

                                <flux:menu.separator />

                                @if($fiche->featured_month)
                                    <flux:menu.heading>
                                        Fiche van de maand &middot; {{ \Carbon\Carbon::createFromFormat('Y-m', $fiche->featured_month)->translatedFormat('M Y') }}
                                    </flux:menu.heading>
                                    <form action="{{ route('fiches.unsetFicheOfMonth', [$initiative, $fiche]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <flux:menu.item type="submit" icon="x-mark">Verwijder als fiche van de maand</flux:menu.item>
                                    </form>
                                @else
                                    <flux:menu.heading>Fiche van de maand</flux:menu.heading>
                                    <div class="px-2 py-1.5">
                                        <form action="{{ route('fiches.setFicheOfMonth', [$initiative, $fiche]) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            <input type="month" name="month" value="{{ now()->format('Y-m') }}" class="text-xs font-medium bg-transparent border border-[var(--color-border-light)] rounded-md px-2 py-1 w-[8rem] focus:outline-none text-[var(--color-text-secondary)]" required>
                                            <flux:button type="submit" size="xs" variant="filled">Stel in</flux:button>
                                        </form>
                                    </div>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                    @endauth
                </div>
            </div>
```

Key changes:
- The right side of the breadcrumb row is now wrapped in `<div class="flex items-center gap-2">` so the edit button and admin dropdown sit side-by-side
- `@can('update', $fiche)` edit button added **before** the admin dropdown (outside `@auth`/`@if(isAdmin())`)
- Duplicate "Bewerk" `<flux:menu.item>` removed from admin dropdown
- The `@can` directive handles auth check implicitly (guests fail the gate)

The **complete** replacement for lines 49–105 of `show.blade.php` (the entire breadcrumb + actions container) is the code block above — use it as the single source of truth.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter="test_author_sees_edit_button|test_curator_sees_edit_button|test_non_author_contributor|test_admin_sees_edit_button_and"`
Expected: ALL PASS

- [ ] **Step 5: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/FicheShowTest.php resources/views/fiches/show.blade.php
git commit -m "feat: add standalone edit button on fiche show page for authorized users"
```

---

## Chunk 2: Author-Aware Comment Placeholder

### Task 2: Add isAuthor computed property and update comment placeholder

**Files:**
- Modify: `app/Livewire/FicheComments.php`
- Modify: `resources/views/livewire/fiche-comments.blade.php`
- Modify: `tests/Feature/FicheShowTest.php`

- [ ] **Step 1: Write tests for comment placeholder copy**

Add to `tests/Feature/FicheShowTest.php`:

```php
public function test_comment_placeholder_shows_author_copy_on_own_fiche(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create([
        'initiative_id' => $initiative->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('fiches.show', [$initiative, $fiche]));

    $response->assertStatus(200);
    $response->assertSee('Voeg een opmerking toe...');
    $response->assertDontSee('Bedank de auteur');
}

public function test_comment_placeholder_shows_default_copy_for_non_author(): void
{
    $user = User::factory()->create();
    $initiative = Initiative::factory()->published()->create();
    $fiche = Fiche::factory()->published()->create([
        'initiative_id' => $initiative->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('fiches.show', [$initiative, $fiche]));

    $response->assertStatus(200);
    $response->assertSee('Bedank de auteur, stel een vraag of deel een tip...');
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="test_comment_placeholder"`
Expected: FAIL — both show the same placeholder currently.

- [ ] **Step 3: Add isAuthor computed property**

Add to `app/Livewire/FicheComments.php`, after the existing `commentCount` computed (around line 152):

```php
#[Computed]
public function isAuthor(): bool
{
    return auth()->id() === $this->fiche->user_id;
}
```

- [ ] **Step 4: Update placeholder in fiche-comments.blade.php**

In `resources/views/livewire/fiche-comments.blade.php`, replace line 123:

```blade
                    placeholder="{{ $this->isAuthor ? 'Voeg een opmerking toe...' : 'Bedank de auteur, stel een vraag of deel een tip...' }}"
```

This is the only location that needs changing — the guest section (lines 157, 160) stays as-is because guests can never be the author.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter="test_comment_placeholder"`
Expected: ALL PASS

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/FicheComments.php resources/views/livewire/fiche-comments.blade.php tests/Feature/FicheShowTest.php
git commit -m "feat: show author-appropriate comment placeholder on own fiche"
```

---

## Chunk 3: Visual Verification

### Task 3: Take screenshots and verify

- [ ] **Step 1: Take screenshot as the fiche author**

Use the screenshot helper to capture the fiche show page while impersonating the fiche's author. Look for:
- Edit button visible on breadcrumb row, right-aligned
- Neutral/ghost style with border (not orange)
- Comment placeholder says "Voeg een opmerking toe..."

- [ ] **Step 2: Take screenshot as a different user**

Verify:
- No edit button visible
- Comment placeholder says "Bedank de auteur..."

- [ ] **Step 3: Run full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS
