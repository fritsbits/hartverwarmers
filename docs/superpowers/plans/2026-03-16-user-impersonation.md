# User Impersonation Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow admins to impersonate any user via the browser — seeing the platform exactly as that user — and switch back via a floating badge.

**Architecture:** Session-based auth swapping. A middleware shares impersonation state with views and blocks admin routes during impersonation. Two entry points (admin user list + contributor profiles) and a floating badge for stopping.

**Tech Stack:** Laravel 12, Blade components, Flux UI, PHPUnit

**Spec:** `docs/superpowers/specs/2026-03-16-user-impersonation-design.md`

---

## Chunk 1: Core Impersonation (Controller, Middleware, Routes)

### Task 1: ImpersonateController — Start Action

**Files:**
- Create: `app/Http/Controllers/Admin/ImpersonateController.php`
- Create: `tests/Feature/ImpersonationTest.php`
- Modify: `routes/web.php:68-81`

- [ ] **Step 1: Create the test file**

Run: `php artisan make:test --phpunit ImpersonationTest`

- [ ] **Step 2: Write failing tests for start action**

Replace the contents of `tests/Feature/ImpersonationTest.php` with:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_start_impersonation(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($target);
        $this->assertEquals($admin->id, session('original_user_id'));
    }

    public function test_non_admin_cannot_start_impersonation(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.impersonate.start', $target));

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_when_starting_impersonation(): void
    {
        $target = User::factory()->create();

        $response = $this->post(route('admin.impersonate.start', $target));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_impersonate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.impersonate.start', $admin));

        $response->assertStatus(403);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_nest_impersonation(): void
    {
        $admin = User::factory()->admin()->create();
        $target1 = User::factory()->create();
        $target2 = User::factory()->create();

        $this->actingAs($admin)
            ->withSession(['original_user_id' => $admin->id])
            ->post(route('admin.impersonate.start', $target2))
            ->assertStatus(403);
    }

    public function test_admin_cannot_impersonate_trashed_user(): void
    {
        $admin = User::factory()->admin()->create();
        $trashed = User::factory()->create();
        $trashed->delete();

        $response = $this->actingAs($admin)->post(route('admin.impersonate.start', $trashed));

        $response->assertStatus(404);
    }
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/ImpersonationTest.php`
Expected: All 6 tests FAIL (routes not defined yet)

- [ ] **Step 4: Add routes to web.php**

In `routes/web.php`, add the import at the top (after line 14, with the other controller imports):

```php
use App\Http\Controllers\Admin\ImpersonateController;
```

Inside the `Route::middleware('auth')` group, **before** the `Route::middleware('admin')->group()` block (before line 69), add:

```php
    // Impersonation (stop must be registered before start to avoid {user} wildcard matching "stop")
    Route::post('/admin/impersonate/stop', [ImpersonateController::class, 'stop'])->name('admin.impersonate.stop');
```

Inside the `Route::middleware('admin')->group(function () {` block (after line 80, before the closing `});`), add:

```php
        Route::post('/admin/impersonate/{user}', [ImpersonateController::class, 'start'])
            ->where('user', '[0-9]+')
            ->name('admin.impersonate.start');
```

- [ ] **Step 5: Create the controller**

Run: `php artisan make:class App/Http/Controllers/Admin/ImpersonateController --no-interaction`

Replace contents with:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonateController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        abort_if($user->trashed(), 404);
        abort_if($user->id === $request->user()->id, 403, 'Je kan jezelf niet nabootsen.');
        abort_if(session()->has('original_user_id'), 403, 'Je bent al iemand aan het nabootsen.');

        $adminId = $request->user()->id;

        session()->put('original_user_id', $adminId);
        Auth::login($user);

        Log::info('Impersonation started', [
            'admin_id' => $adminId,
            'target_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->back()->with('success', "Je bekijkt de site nu als {$user->full_name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $originalId = session('original_user_id');

        abort_unless($originalId, 403);

        $admin = User::findOrFail($originalId);

        Log::info('Impersonation stopped', [
            'admin_id' => $originalId,
            'ip' => $request->ip(),
        ]);

        Auth::login($admin);
        session()->forget('original_user_id');
        session()->regenerate();

        return redirect()->route('admin.users.index')->with('success', 'Je bent terug als jezelf.');
    }
}
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/ImpersonationTest.php`
Expected: All 6 tests PASS

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/ImpersonateController.php tests/Feature/ImpersonationTest.php routes/web.php
git commit -m "feat: add impersonation start action with tests"
```

---

### Task 2: ImpersonateController — Stop Action + Tests

**Files:**
- Modify: `tests/Feature/ImpersonationTest.php`

- [ ] **Step 1: Add stop action tests**

Append these tests to the `ImpersonationTest` class:

```php
    public function test_admin_can_stop_impersonation(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        // Start impersonation
        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        // Stop impersonation
        $response = $this->post(route('admin.impersonate.stop'));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('original_user_id'));
    }

    public function test_stop_without_impersonation_returns_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.impersonate.stop'));

        $response->assertStatus(403);
    }

    public function test_logout_while_impersonating_ends_session(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));
        $this->post(route('logout'));

        $this->assertGuest();
    }

    public function test_admin_can_impersonate_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $response = $this->actingAs($admin1)->post(route('admin.impersonate.start', $admin2));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin2);
        $this->assertEquals($admin1->id, session('original_user_id'));
    }
```

- [ ] **Step 2: Run tests**

Run: `php artisan test --compact tests/Feature/ImpersonationTest.php`
Expected: All 10 tests PASS (stop action already implemented in Task 1)

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/ImpersonationTest.php
git commit -m "test: add stop impersonation and edge case tests"
```

---

### Task 3: HandleImpersonation Middleware

**Files:**
- Create: `app/Http/Middleware/HandleImpersonation.php`
- Modify: `bootstrap/app.php:13-18`
- Modify: `tests/Feature/ImpersonationTest.php`

- [ ] **Step 1: Add middleware tests**

Append these tests to the `ImpersonationTest` class:

```php
    public function test_admin_routes_blocked_while_impersonating(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $response = $this->get(route('admin.features'));

        $response->assertStatus(403);
    }

    public function test_admin_post_routes_blocked_while_impersonating(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $response = $this->post(route('admin.features.toggle', 'some-feature'));

        $response->assertStatus(403);
    }

    public function test_stop_route_accessible_while_impersonating(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $response = $this->post(route('admin.impersonate.stop'));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertAuthenticatedAs($admin);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=test_admin_routes_blocked_while_impersonating`
Expected: FAIL (no middleware blocking admin routes yet)

- [ ] **Step 3: Create the middleware**

Run: `php artisan make:middleware HandleImpersonation --no-interaction`

Replace contents with:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $isImpersonating = session()->has('original_user_id');

        View::share('isImpersonating', $isImpersonating);
        View::share('originalUserId', session('original_user_id'));

        if ($isImpersonating) {
            $routeName = $request->route()?->getName() ?? '';

            $blockedRoutes = str_starts_with($routeName, 'admin.') && $routeName !== 'admin.impersonate.stop';
            $blockedRoutes = $blockedRoutes || $routeName === 'pulse';

            if ($blockedRoutes) {
                abort(403, 'Stop eerst met nabootsen voordat je admin-pagina\'s bezoekt.');
            }
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the middleware globally**

In `bootstrap/app.php`, inside the `->withMiddleware()` callback, after line 18 (`$middleware->appendToGroup('web', ...)`), add:

```php
        $middleware->appendToGroup('web', \App\Http\Middleware\HandleImpersonation::class);
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact tests/Feature/ImpersonationTest.php`
Expected: All tests PASS

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Http/Middleware/HandleImpersonation.php bootstrap/app.php tests/Feature/ImpersonationTest.php
git commit -m "feat: add HandleImpersonation middleware blocking admin routes"
```

---

## Chunk 2: UI Components

### Task 4: Impersonation Badge

**Files:**
- Create: `resources/views/components/impersonation-badge.blade.php`
- Modify: `resources/views/components/layout.blade.php:158`
- Modify: `tests/Feature/ImpersonationTest.php`

- [ ] **Step 1: Add badge rendering tests**

Append these tests to the `ImpersonationTest` class:

```php
    public function test_badge_renders_when_impersonating(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create(['first_name' => 'Jan', 'last_name' => 'Peeters']);

        $this->actingAs($admin)->post(route('admin.impersonate.start', $target));

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Jan Peeters');
        $response->assertSee('Stop');
        $response->assertSee(route('admin.impersonate.stop'));
    }

    public function test_badge_hidden_when_not_impersonating(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertStatus(200);
        $response->assertDontSee(route('admin.impersonate.stop'));
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=test_badge_renders_when_impersonating`
Expected: FAIL (badge component doesn't exist yet)

- [ ] **Step 3: Create the badge component**

Create `resources/views/components/impersonation-badge.blade.php`:

```blade
@if(session('original_user_id'))
    @php $impersonatedUser = auth()->user(); @endphp
    <div
        id="impersonation-badge"
        role="status"
        aria-label="Je bekijkt de site als {{ $impersonatedUser->full_name }}"
        style="
            position: fixed;
            bottom: 90px;
            left: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #92400e;
            color: white;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-family: system-ui, sans-serif;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            z-index: 9999;
        "
    >
        <x-user-avatar :user="$impersonatedUser" size="xs" />
        <span>{{ $impersonatedUser->full_name }}</span>
        <span style="
            display: inline-block;
            padding: 1px 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
        ">{{ $impersonatedUser->role }}</span>
        <form method="POST" action="{{ route('admin.impersonate.stop') }}" style="display: inline; margin: 0;">
            @csrf
            <button type="submit" style="
                background: rgba(255,255,255,0.9);
                color: #92400e;
                border: none;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
                font-family: inherit;
            ">Stop</button>
        </form>
    </div>
@endif
```

- [ ] **Step 4: Include badge in layout**

In `resources/views/components/layout.blade.php`, after line 158 (`<x-dev.queue-badge />`), add:

```blade
    <x-impersonation-badge />
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact tests/Feature/ImpersonationTest.php`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/impersonation-badge.blade.php resources/views/components/layout.blade.php tests/Feature/ImpersonationTest.php
git commit -m "feat: add impersonation floating badge"
```

---

### Task 5: Admin User List Page

**Files:**
- Create: `app/Http/Controllers/Admin/AdminUserController.php`
- Create: `resources/views/admin/users/index.blade.php`
- Modify: `routes/web.php` (add route)
- Modify: `resources/views/components/nav.blade.php:218` (add nav link)

- [ ] **Step 1: Add route**

In `routes/web.php`, add the import at top:

```php
use App\Http\Controllers\Admin\AdminUserController;
```

Inside the `Route::middleware('admin')->group()` block (after the `Route::get('/admin/mails'...` line), add:

```php
        Route::get('/admin/gebruikers', [AdminUserController::class, 'index'])->name('admin.users.index');
```

- [ ] **Step 2: Create the controller**

Run: `php artisan make:class App/Http/Controllers/Admin/AdminUserController --no-interaction`

Replace contents with:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'curator' THEN 2 WHEN 'contributor' THEN 3 WHEN 'member' THEN 4 ELSE 5 END")
            ->orderBy('first_name')
            ->get();

        return view('admin.users.index', ['users' => $users]);
    }
}
```

- [ ] **Step 3: Create the view**

Create `resources/views/admin/users/index.blade.php`:

```blade
<x-layout title="Gebruikers" :full-width="true">
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <span class="section-label">Admin</span>
            <h1 class="text-3xl mt-1">Gebruikers</h1>
        </div>
    </section>

    <section>
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border-light)] text-left text-[var(--color-text-secondary)]">
                            <th class="pb-3 font-medium">Gebruiker</th>
                            <th class="pb-3 font-medium">E-mail</th>
                            <th class="pb-3 font-medium">Rol</th>
                            <th class="pb-3 font-medium">Organisatie</th>
                            <th class="pb-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr class="border-b border-[var(--color-border-light)]">
                                <td class="py-3">
                                    <div class="flex items-center gap-3">
                                        <x-user-avatar :user="$user" size="sm" />
                                        <span class="font-medium">{{ $user->full_name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 text-[var(--color-text-secondary)]">{{ $user->email }}</td>
                                <td class="py-3">
                                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full
                                        {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $user->role === 'curator' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $user->role === 'contributor' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $user->role === 'member' ? 'bg-gray-100 text-gray-800' : '' }}
                                    ">{{ $user->role }}</span>
                                </td>
                                <td class="py-3 text-[var(--color-text-secondary)]">{{ $user->organisation ?? '—' }}</td>
                                <td class="py-3 text-right">
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.impersonate.start', $user) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-[var(--color-primary)] hover:underline cursor-pointer">
                                                Nabootsen
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layout>
```

- [ ] **Step 4: Add nav link**

In `resources/views/components/nav.blade.php`, after line 218 (the Pulse menu item inside the admin `@if` block), add:

```blade
                                <flux:menu.item href="{{ route('admin.users.index') }}" icon="users">Gebruikers</flux:menu.item>
```

- [ ] **Step 5: Add test for admin user list page**

Append this test to the `ImpersonationTest` class:

```php
    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->admin()->create();
        $contributor = User::factory()->create(['first_name' => 'Marie', 'last_name' => 'Janssen']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Marie Janssen');
        $response->assertSee('Nabootsen');
    }

    public function test_non_admin_cannot_view_user_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --compact tests/Feature/ImpersonationTest.php`
Expected: All 17 tests PASS

- [ ] **Step 7: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Build frontend assets**

Run: `npm run build`

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Admin/AdminUserController.php resources/views/admin/users/index.blade.php resources/views/components/nav.blade.php routes/web.php
git commit -m "feat: add admin user list page with impersonate buttons"
```

---

### Task 6: Impersonate Button on Contributor Profiles

**Files:**
- Modify: `resources/views/contributors/show.blade.php:80-81`

- [ ] **Step 1: Add the impersonate button**

In `resources/views/contributors/show.blade.php`, after line 80 (after the closing `@endif` of the social links block), and before line 82 (`</div>` that closes the `flex-1 min-w-0` div), add:

```blade

                    {{-- Admin: impersonate --}}
                    @auth
                        @if(auth()->user()->isAdmin() && !$isOwnProfile)
                            <form method="POST" action="{{ route('admin.impersonate.start', $contributor) }}" class="mt-5">
                                @csrf
                                <button type="submit" class="btn-pill text-xs">
                                    Bekijk als deze gebruiker
                                </button>
                            </form>
                        @endif
                    @endauth
```

- [ ] **Step 2: Run all impersonation tests**

Run: `php artisan test --compact tests/Feature/ImpersonationTest.php`
Expected: All tests PASS

- [ ] **Step 3: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git add resources/views/contributors/show.blade.php
git commit -m "feat: add impersonate button on contributor profile pages"
```

---

## Chunk 3: Visual Verification & Final Test Suite

### Task 7: Full Test Suite Run

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test --compact`
Expected: All tests PASS — no regressions

- [ ] **Step 2: Take screenshots to verify badge and user list**

Take a screenshot of the admin user list page:
```bash
node scripts/screenshot.cjs /admin/gebruikers /tmp/admin-users.png
```

Start impersonation (manually or via tinker), then take a screenshot of the homepage to verify the badge:
```bash
node scripts/screenshot.cjs / /tmp/impersonation-badge.png
```

- [ ] **Step 3: Verify and fix any visual issues**

Review the screenshots. The badge should appear bottom-left, above the queue badge. The user list should show all users with role badges and impersonate buttons.

- [ ] **Step 4: Final commit if any visual fixes were needed**

```bash
git add -A
git commit -m "fix: visual adjustments to impersonation UI"
```
