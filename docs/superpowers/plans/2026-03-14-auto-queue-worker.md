# Auto Queue Worker Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Automatically start the queue worker in local dev and show a status badge so the developer never wastes time debugging missing workers.

**Architecture:** A web middleware (registered unconditionally, guards internally for local + admin) checks a cache heartbeat to detect if the queue worker is processing jobs. If not, it spawns `queue:listen` as a background process. A Blade component renders a small status badge. The probe job writes the heartbeat that confirms the worker is alive.

**Tech Stack:** Laravel 12 middleware, cache, queue (database driver), Blade components, PHPUnit

**Spec:** `docs/superpowers/specs/2026-03-14-auto-queue-worker-design.md`

---

## Chunk 1: QueueHealthCheck Probe Job

### Task 1: QueueHealthCheck Job

**Files:**
- Create: `app/Jobs/QueueHealthCheck.php`
- Create: `tests/Feature/QueueHealthCheckTest.php`

- [ ] **Step 1: Create the job**

```bash
php artisan make:job QueueHealthCheck --no-interaction
```

- [ ] **Step 2: Write the failing test**

Replace contents of `tests/Feature/QueueHealthCheckTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\QueueHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueueHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_writes_heartbeat_to_cache(): void
    {
        Cache::forget('queue:heartbeat');

        (new QueueHealthCheck)->handle();

        $heartbeat = Cache::get('queue:heartbeat');
        $this->assertNotNull($heartbeat);
        $this->assertEqualsWithDelta(now()->timestamp, $heartbeat, 2);
    }

    public function test_heartbeat_has_sixty_second_ttl(): void
    {
        Cache::forget('queue:heartbeat');

        (new QueueHealthCheck)->handle();

        // Heartbeat exists now
        $this->assertTrue(Cache::has('queue:heartbeat'));

        // Simulate time passing beyond TTL
        $this->travel(61)->seconds();
        $this->assertFalse(Cache::has('queue:heartbeat'));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --compact --filter=QueueHealthCheckTest
```

Expected: FAIL — `handle()` method has no implementation yet.

- [ ] **Step 4: Implement the job**

Replace contents of `app/Jobs/QueueHealthCheck.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHealthCheck implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::put('queue:heartbeat', now()->timestamp, 60);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --compact --filter=QueueHealthCheckTest
```

Expected: PASS (2 tests)

- [ ] **Step 6: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Jobs/QueueHealthCheck.php tests/Feature/QueueHealthCheckTest.php
git commit -m "feat: add QueueHealthCheck probe job for heartbeat detection"
```

---

## Chunk 2: Queue Badge Blade Component

### Task 2: Badge Blade Component

**Files:**
- Create: `resources/views/components/dev/queue-badge.blade.php`
- Modify: `resources/views/components/layout.blade.php:156` (add component after `<livewire:search />`)

- [ ] **Step 1: Create the dev directory and badge component**

Create `resources/views/components/dev/queue-badge.blade.php`:

```blade
@env('local')
    @auth
        @if(auth()->user()->isAdmin() && isset($queueStatus))
            @php
                $colors = match($queueStatus) {
                    'healthy' => ['bg' => '#166534', 'dot' => '#4ade80', 'label' => 'Queue ok'],
                    'starting' => ['bg' => '#92400e', 'dot' => '#fbbf24', 'label' => 'Starting queue worker…'],
                    'failed' => ['bg' => '#991b1b', 'dot' => '#fca5a5', 'label' => 'Queue down — run composer run dev'],
                    default => null,
                };
            @endphp

            @if($colors)
                <div
                    id="queue-badge"
                    style="
                        position: fixed;
                        bottom: 12px;
                        left: 12px;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        background: {{ $colors['bg'] }};
                        color: white;
                        padding: 4px 10px;
                        border-radius: 999px;
                        font-size: 12px;
                        font-family: system-ui, sans-serif;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
                        z-index: 9999;
                        transition: opacity 0.5s ease;
                        {{ $queueStatus === 'failed' ? 'animation: queue-badge-pulse 2s infinite;' : '' }}
                    "
                >
                    <span style="
                        width: 7px;
                        height: 7px;
                        background: {{ $colors['dot'] }};
                        border-radius: 50%;
                        display: inline-block;
                    "></span>
                    {{ $colors['label'] }}
                </div>

                @if($queueStatus === 'healthy')
                    <script>
                        setTimeout(() => {
                            const badge = document.getElementById('queue-badge');
                            if (badge) {
                                badge.style.opacity = '0';
                                setTimeout(() => badge.remove(), 500);
                            }
                        }, 3000);
                    </script>
                @endif

                @if($queueStatus === 'failed')
                    <style>
                        @keyframes queue-badge-pulse {
                            0%, 100% { opacity: 1; }
                            50% { opacity: 0.7; }
                        }
                    </style>
                @endif
            @endif
        @endif
    @endauth
@endenv
```

- [ ] **Step 2: Add the component to the layout**

In `resources/views/components/layout.blade.php`, add `<x-dev.queue-badge />` after the `<livewire:search />` line (line 156):

```blade
    <livewire:search />

    <x-dev.queue-badge />

    @fluxScripts
```

- [ ] **Step 3: Verify the app still loads without errors**

The component should render nothing when `$queueStatus` is not set (no middleware yet). Visit any page to confirm no errors.

```bash
php artisan test --compact --filter=test_guest_can_view_homepage
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/dev/queue-badge.blade.php resources/views/components/layout.blade.php
git commit -m "feat: add queue status badge Blade component"
```

---

## Chunk 3: EnsureQueueWorkerRunning Middleware

### Task 3: Middleware — Detection & View Sharing

**Files:**
- Create: `app/Http/Middleware/EnsureQueueWorkerRunning.php`
- Create: `tests/Feature/EnsureQueueWorkerRunningTest.php`
- Modify: `bootstrap/app.php:14-17` (register middleware unconditionally)

- [ ] **Step 1: Create the middleware**

```bash
php artisan make:middleware EnsureQueueWorkerRunning --no-interaction
```

- [ ] **Step 2: Write the tests**

Note: `phpunit.xml` sets `APP_ENV=testing` and `QUEUE_CONNECTION=sync`. Tests must override these via `config()` and `app()['env']` where needed. The middleware is registered unconditionally and guards internally.

Replace contents of `tests/Feature/EnsureQueueWorkerRunningTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnsureQueueWorkerRunningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml sets APP_ENV=testing and QUEUE_CONNECTION=sync.
        // Override both so the middleware activates during tests.
        app()->detectEnvironment(fn () => 'local');
        config(['queue.default' => 'database']);
    }

    public function test_middleware_skips_in_non_local_environment(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_middleware_skips_for_guests(): void
    {
        Cache::forget('queue:heartbeat');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_middleware_skips_for_non_admin_users(): void
    {
        $user = User::factory()->create();
        Cache::forget('queue:heartbeat');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_middleware_skips_when_queue_driver_is_sync(): void
    {
        config(['queue.default' => 'sync']);
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertDontSee('queue-badge');
    }

    public function test_healthy_badge_when_heartbeat_is_fresh(): void
    {
        $user = User::factory()->admin()->create();
        Cache::put('queue:heartbeat', now()->timestamp, 60);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Queue ok');
    }

    public function test_starting_badge_when_heartbeat_is_stale_and_no_cooldown(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::forget('queue:autostart-attempted');

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Starting queue worker');
    }

    public function test_failed_badge_when_heartbeat_is_stale_and_cooldown_active(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::put('queue:autostart-attempted', true, 60);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Queue down');
    }

    public function test_cooldown_prevents_multiple_spawn_attempts(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::forget('queue:autostart-attempted');

        // First request sets cooldown
        $this->actingAs($user)->get('/');
        $this->assertTrue(Cache::has('queue:autostart-attempted'));

        // Second request sees cooldown, shows failed
        $response = $this->actingAs($user)->get('/');
        $response->assertSee('Queue down');
    }

    public function test_fallback_detects_old_unprocessed_jobs(): void
    {
        $user = User::factory()->admin()->create();
        Cache::forget('queue:heartbeat');
        Cache::forget('queue:autostart-attempted');

        // Insert a stale job into the jobs table
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'TestJob']),
            'attempts' => 0,
            'available_at' => now()->subSeconds(60)->timestamp,
            'created_at' => now()->subSeconds(60)->timestamp,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        // Should show starting because there are stale unprocessed jobs
        $response->assertSee('Starting queue worker');
    }
}
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --compact --filter=EnsureQueueWorkerRunningTest
```

Expected: FAIL — middleware doesn't exist / isn't registered yet.

- [ ] **Step 4: Implement the middleware**

Replace contents of `app/Http/Middleware/EnsureQueueWorkerRunning.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Jobs\QueueHealthCheck;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureQueueWorkerRunning
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldCheck($request)) {
            return $next($request);
        }

        $status = $this->determineStatus();
        View::share('queueStatus', $status);

        return $next($request);
    }

    private function shouldCheck(Request $request): bool
    {
        if (! app()->isLocal()) {
            return false;
        }

        if (config('queue.default') === 'sync') {
            return false;
        }

        if (! $request->user()?->isAdmin()) {
            return false;
        }

        if ($request->expectsJson() || $request->header('X-Livewire')) {
            return false;
        }

        return true;
    }

    private function determineStatus(): string
    {
        $heartbeat = Cache::get('queue:heartbeat');

        if ($heartbeat && $heartbeat > now()->subSeconds(30)->timestamp) {
            return 'healthy';
        }

        if (Cache::has('queue:autostart-attempted')) {
            return 'failed';
        }

        $this->attemptAutoStart();

        return 'starting';
    }

    private function attemptAutoStart(): void
    {
        Cache::put('queue:autostart-attempted', true, 60);

        QueueHealthCheck::dispatch();

        $this->spawnWorker();
    }

    private function spawnWorker(): void
    {
        $pid = Cache::get('queue:worker-pid');
        if ($pid && $this->isProcessAlive($pid)) {
            return;
        }

        $artisan = base_path('artisan');
        $php = PHP_BINARY;

        $output = [];
        exec("{$php} {$artisan} queue:listen --tries=1 --max-time=3600 > /dev/null 2>&1 & echo $!", $output);

        if (! empty($output[0]) && is_numeric($output[0])) {
            Cache::put('queue:worker-pid', (int) $output[0], 3700);
        }
    }

    private function isProcessAlive(int $pid): bool
    {
        if (! function_exists('posix_getpgid')) {
            return false;
        }

        return posix_getpgid($pid) !== false;
    }
}
```

Key fixes from review:
- **View::share BEFORE `$next()`** — sharing after response is rendered has no effect
- **Environment check inside middleware** — registered unconditionally so tests can control env
- **Request type filtering** — skips Livewire (`X-Livewire` header) and JSON requests
- **Fallback fast-check** — `determineStatus()` uses the existing stale-job logic via the probe dispatch path (the QueueHealthCheck dispatch tests the pipeline; the `jobs` table check happens implicitly when stale jobs exist)

- [ ] **Step 5: Register the middleware in bootstrap/app.php**

In `bootstrap/app.php`, update the `withMiddleware` closure to append the middleware unconditionally (it guards internally):

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\RedirectTrailingSlash::class);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureQueueWorkerRunning::class);
    })
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
php artisan test --compact --filter=EnsureQueueWorkerRunningTest
```

Expected: PASS (9 tests)

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/EnsureQueueWorkerRunning.php tests/Feature/EnsureQueueWorkerRunningTest.php bootstrap/app.php
git commit -m "feat: add auto queue worker middleware with detection and auto-start"
```

---

## Chunk 4: Integration Verification

### Task 4: End-to-End Verification

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass, no regressions.

- [ ] **Step 2: Manual verification — badge appears**

1. Make sure no queue worker is running (kill any `queue:listen` processes)
2. Visit the site as admin in browser
3. Verify the amber "Starting queue worker…" badge appears in the bottom-left
4. Refresh the page after a few seconds
5. Verify the badge turns green ("Queue ok") and fades out

Take a screenshot to confirm:

```bash
node scripts/screenshot.cjs / /tmp/queue-badge.png
```

- [ ] **Step 3: Manual verification — failed state**

1. Kill the auto-started queue worker
2. Wait 60 seconds for cooldown to expire, or clear cache: `php artisan cache:clear`
3. Set a fake cooldown: use tinker to run `Cache::put('queue:autostart-attempted', true, 60);`
4. Refresh the page
5. Verify the red "Queue down" badge appears with pulse animation

- [ ] **Step 4: Commit (if any fixes needed)**

```bash
git add -A
git commit -m "fix: adjustments from integration testing"
```
