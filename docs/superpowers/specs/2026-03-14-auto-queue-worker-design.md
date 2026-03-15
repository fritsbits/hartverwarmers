# Auto Queue Worker — Design Spec

## Problem

When developing locally, Laravel Herd serves the site automatically over HTTPS — but the queue worker only runs when `composer run dev` is started manually. Forgetting to start it causes queued jobs (file processing, previews, text extraction) to silently pile up. The developer wastes time debugging what looks like a broken feature but is just a missing worker.

## Solution

A middleware that automatically detects whether the queue worker is running and starts it if needed. Only active in `local` environment for admin users. A small floating badge provides visual feedback on queue health.

## Design Decisions

- **Admin-only, local-only** — zero impact on production or regular users
- **Auto-start the queue worker only** — not Vite or Pail, since you notice those yourself (no hot reload, no logs)
- **Middleware-based** — runs on every web request, no extra processes to remember
- **End-to-end detection** — dispatches a probe job rather than checking process lists, so it tests the actual pipeline
- **Fallback warning** — if auto-start fails, shows a persistent badge with instructions
- **Sync driver guard** — early return when `QUEUE_CONNECTION=sync` (jobs run inline, no worker needed)

## Detection Strategy

1. On each request, the middleware checks `cache('queue:heartbeat')` for a recent timestamp (< 30 seconds old)
2. If fresh → worker is alive, show green badge briefly (fades after 3s)
3. If stale or missing:
   - Check `cache('queue:autostart-attempted')` cooldown (60s)
   - If no recent attempt → dispatch `QueueHealthCheck` probe job, spawn worker process, set cooldown, show amber "Starting…" badge
   - If cooldown active but heartbeat still stale → auto-start failed, show red "Queue down" badge

**Throttling**: Detection logic (cache check + possible spawn) only runs on full-page HTML responses. Livewire updates, AJAX requests, and JSON responses skip detection entirely. Check via `Content-Type` header containing `text/html` and the presence of `</body>` in the response.

### QueueHealthCheck Probe Job

A minimal job that writes `now()->timestamp` to `cache('queue:heartbeat', $timestamp, 60)`. Dispatched via `dispatch()` (never `dispatchSync`) — the whole point is to test whether the async pipeline works. TTL of 60 seconds means a missing heartbeat reliably indicates a dead worker. The 30s "fresh" threshold vs 60s cache TTL is intentional — it provides a buffer window before the key expires entirely.

### Fallback Fast-Check

On first request (no heartbeat exists yet), also check the `jobs` table for unprocessed jobs older than 30 seconds. Query:

```php
DB::table('jobs')->where('available_at', '<=', now()->subSeconds(30)->timestamp)->exists()
```

The `available_at` column uses Unix timestamps. This catches "worker was never started" immediately without waiting for a probe round-trip.

## Auto-Start Mechanism

1. **Check for existing worker**: Before spawning, validate `cache('queue:worker-pid')` — if a PID exists, check if it's still alive via `posix_getpgid($pid)`. If alive, skip spawning (avoids duplicate workers when `composer run dev` is also running)
2. **Spawn**: Use `exec('php artisan queue:listen --tries=1 --max-time=3600 > /dev/null 2>&1 & echo $!')` to reliably detach the process on macOS. `exec()` with `&` is more reliable than `proc_open` under Laravel Herd's PHP-FPM. The `--max-time=3600` flag ensures the auto-started worker self-terminates after 1 hour, preventing orphaned workers from accumulating across development sessions.
3. **Store PID** from the `exec` output in `cache('queue:worker-pid', $pid, 3700)` (slightly longer than max-time)
4. **Set cooldown**: `cache('queue:autostart-attempted', true, 60)` to prevent retry storms
5. On the next request, the normal heartbeat check confirms whether the worker came alive
6. If heartbeat is still stale after cooldown expires → show red badge, do not retry (something is broken)

### Worker Lifecycle

- **Self-termination**: `--max-time=3600` means auto-started workers die after 1 hour. If the developer is still working, the next request will spawn a fresh one.
- **Duplicate prevention**: PID check before spawning prevents running multiple workers. If `composer run dev` is started later, its worker handles jobs and keeps the heartbeat fresh — the auto-started worker will naturally expire via `--max-time`.
- **No manual cleanup needed**: Workers are fire-and-forget with a built-in TTL.

## Badge UI

A Blade component included in the main layout file, guarded by `@env('local')` and admin check. This is more idiomatic than response body manipulation and avoids issues with Livewire partial responses.

### Approach

The middleware sets a view-shared variable (`queue_status`) with one of three values: `healthy`, `starting`, `failed`. The Blade component reads this to render the appropriate badge state. On non-HTML / Livewire requests, the middleware skips entirely — no view data is shared.

### Three States

| State | Color | Behavior |
|-------|-------|----------|
| **Healthy** | Green (`#166534` bg, `#4ade80` dot) | Shows on page load, fades out after 3 seconds |
| **Starting** | Amber (`#92400e` bg, `#fbbf24` dot) | Shows while auto-start is in progress |
| **Failed** | Red (`#991b1b` bg, `#fca5a5` dot) | Stays visible with gentle pulse, text: "Queue down — run `composer run dev`" |

### Styling

- Pill shape: `border-radius: 999px`, `font-size: 12px`, system-ui font
- Small status dot (7px) before text label
- Subtle box-shadow for depth
- No JavaScript framework — inline styles + minimal `<style>` block for fade/pulse animation
- Z-index high enough to float above page content

## File Structure

| File | Purpose |
|------|---------|
| `app/Http/Middleware/EnsureQueueWorkerRunning.php` | Detection, auto-start, sets view-shared queue status |
| `app/Jobs/QueueHealthCheck.php` | Probe job — writes heartbeat to cache |
| `resources/views/components/dev/queue-badge.blade.php` | Badge Blade component (3 states) |

## Middleware Registration

In `bootstrap/app.php`, append to the `web` middleware stack with a boot-time environment guard:

```php
->withMiddleware(function (Middleware $middleware) {
    // ... existing middleware ...
    if (app()->isLocal()) {
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureQueueWorkerRunning::class);
    }
})
```

The middleware itself also checks `auth()->user()?->isAdmin()` and `config('queue.default') !== 'sync'` before doing any work.

## Request Flow

```
Request arrives
  → Middleware: queue driver is 'sync'? → skip entirely
  → Middleware: user is admin? No → skip entirely
  → Response is full-page HTML? No → skip detection, pass through
  → Check cache('queue:heartbeat') fresh (< 30s)?
    → Yes → share queue_status = 'healthy', done
    → No → cooldown active?
      → No → check PID alive? No → dispatch QueueHealthCheck, spawn queue:listen, set cooldown
             → share queue_status = 'starting'
      → Yes → heartbeat still stale?
             → share queue_status = 'failed'
  → Blade component renders badge based on queue_status
```

## What This Does NOT Do

- Does not start Vite or Pail (you notice those yourself)
- Does not run in production or staging
- Does not show anything to non-admin users
- Does not add any npm/composer dependencies
- Does not require new migrations or config files
- Does not retry auto-start endlessly (one attempt per 60s cooldown)
- Does not interfere with Livewire/AJAX requests
- Does not run when queue driver is `sync`

## Testing

- Feature test: middleware skips in non-local environment
- Feature test: middleware skips for non-admin users
- Feature test: middleware skips when queue driver is `sync`
- Feature test: QueueHealthCheck job writes heartbeat to cache with correct TTL
- Feature test: view shares `healthy` status when heartbeat is fresh
- Feature test: view shares `starting` status when heartbeat is stale and no cooldown
- Feature test: view shares `failed` status when heartbeat is stale and cooldown is active
- Feature test: cooldown prevents multiple spawn attempts within 60s
- Feature test: badge component renders correct HTML for each state
- Feature test: fallback fast-check detects old unprocessed jobs
