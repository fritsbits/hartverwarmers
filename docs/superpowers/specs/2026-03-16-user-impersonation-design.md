# User Impersonation Design

## Problem

No way to see the platform as a specific user during manual browser testing or production support. Admin must log out and log in as another user, which is slow and loses context.

## Solution

Session-based user impersonation. Admins can "become" any user via two entry points, see the platform exactly as that user would, and switch back via a floating badge.

## Routes & Controller

### Routes

```
POST /admin/impersonate/{user}   → ImpersonateController@start   (name: admin.impersonate.start)
POST /admin/impersonate/stop     → ImpersonateController@stop    (name: admin.impersonate.stop)
```

- `start` is protected by the existing `admin` middleware (which includes `auth`).
- `stop` is behind `auth` middleware only (not `admin`, since the logged-in user is the impersonated non-admin). It checks for `session('original_user_id')`.

### ImpersonateController

**`start(User $user)`**:
1. Reject if `$user->id === auth()->id()` (can't impersonate yourself)
2. Reject if `session('original_user_id')` already set (no nesting)
3. Reject if `$user->trashed()` (no soft-deleted users)
4. Store `session('original_user_id', auth()->id())`
5. `Auth::login($user)` (without remember — remember-me state is intentionally not preserved)
6. `Log::info('Impersonation started', ['admin_id' => $originalId, 'target_id' => $user->id, 'ip' => $request->ip()])`
7. Redirect back with flash message

**`stop()`**:
1. Read `session('original_user_id')`
2. Abort 403 if not set
3. `Auth::login(User::findOrFail($originalId))` (without remember)
4. `Log::info('Impersonation stopped', ['admin_id' => $originalId, 'ip' => $request->ip()])`
5. `session()->forget('original_user_id')`
6. `session()->regenerate()` (safe here — session key no longer needed)
7. Redirect to `/admin/gebruikers` with flash message

### Session regeneration strategy

- **In `start()`**: Do NOT regenerate session after `Auth::login()`, because `original_user_id` was just stored and would be lost.
- **In `stop()`**: Regenerate session after forgetting the key — restores clean session state for the admin.
- **CSRF tokens**: Blade's `@csrf` renders a fresh token on each page load, so the stop-form always has a valid token.

## Middleware: HandleImpersonation

Registered globally in `bootstrap/app.php`.

Responsibilities:
- Share `is_impersonating` and `original_user_id` with all views via `View::share()`
- Block access to admin routes (by route name prefix `admin.`, except `admin.impersonate.stop`) while impersonating — returns 403 with message explaining they must stop impersonating first

Uses route-name-based checking (not URL prefix) to avoid fragility with future route changes.

## Floating Badge

Blade component: `<x-impersonation-badge />`

Included in the main layout (`components/layout.blade.php`). Only renders when `session('original_user_id')` is set.

### Appearance
- Fixed bottom-left, positioned above the queue badge (`bottom: 90px; left: 12px`)
- Pill shape, orange/amber background (`#92400e` bg, white text) — warm, visible, non-alarming
- Shows: user avatar (small circle, or initials fallback) + full name + role badge + "Stop" button
- Inline styles matching the queue badge pattern
- `z-index: 9999`
- `role="status"` and `aria-label` for screen reader accessibility

### Stop button
- Small form inside the badge: `POST /admin/impersonate/stop` with CSRF token
- Styled as a small white/light button within the pill
- Keyboard-accessible

## User Selection Entry Points

### a) Admin user list: `/admin/gebruikers`

New admin-only page (route name: `admin.users.index`).

- Simple table: avatar, name, email, role, organisation
- Each row has an "Impersonate" button (POST form) — not shown for the current admin's own row
- Protected by existing `admin` middleware
- Controller: `AdminUserController@index`
- Basic search/filter not required for v1 but the page structure should allow it later

### b) Contributor profile pages: `/bijdragers/{user}`

- When an admin views another user's profile, show a "Bekijk als deze gebruiker" button
- Only visible when `auth()->user()->isAdmin()` and the profile is NOT the admin's own
- Small secondary-style button, positioned near the profile header
- POST form to `/admin/impersonate/{user}`

## Security

| Rule | Implementation |
|------|---------------|
| Admin-only initiation | `admin` middleware on `start` route |
| Auth required for stop | `auth` middleware on `stop` route |
| No self-impersonation | Controller check: reject if target === self |
| No nesting | Controller check: reject if session key exists |
| No trashed users | Controller check: `$user->trashed()` |
| Session-scoped | Ends on logout or session expiry |
| Admin routes blocked | Middleware blocks routes with `admin.*` name prefix, except stop |
| Stop always available | `stop` route checks session key, not admin role |
| Audit trail | `Log::info()` on start and stop with admin ID, target ID, IP |
| No remember-me | `Auth::login()` called without remember flag in both directions |

## Testing

### Feature Tests (`tests/Feature/ImpersonationTest.php`)

1. **Admin can start impersonation**: POST to start, assert session has `original_user_id`, assert auth user is target
2. **Admin can stop impersonation**: Start, then POST to stop, assert auth user is original admin, assert session key cleared
3. **Non-admin gets 403**: Contributor/member POSTing to start route gets 403
4. **Can't impersonate yourself**: Admin POSTing with own ID gets rejected
5. **Can't nest impersonation**: Already impersonating, POST to start again gets rejected
6. **Can't impersonate trashed user**: Soft-deleted user target gets rejected
7. **Admin routes blocked while impersonating**: GET `/admin/gebruikers` returns 403 while impersonating
8. **Admin POST routes blocked while impersonating**: POST to admin feature toggle returns 403 while impersonating
9. **Stop route accessible while impersonating**: POST to stop succeeds even though auth user is non-admin
10. **Badge renders when impersonating**: Response contains impersonation badge markup when session key set
11. **Badge hidden when not impersonating**: Response does NOT contain badge markup normally
12. **Logout while impersonating ends impersonation**: Session destroyed, admin must re-login as themselves
13. **Admin can impersonate another admin**: Impersonated admin is blocked from admin routes like any other impersonated user

## Files to Create/Modify

### New files
- `app/Http/Controllers/Admin/ImpersonateController.php`
- `app/Http/Controllers/Admin/AdminUserController.php`
- `app/Http/Middleware/HandleImpersonation.php`
- `resources/views/components/impersonation-badge.blade.php`
- `resources/views/admin/users/index.blade.php`
- `tests/Feature/ImpersonationTest.php`

### Modified files
- `routes/web.php` — add impersonation routes + admin user list route
- `bootstrap/app.php` — register `HandleImpersonation` middleware globally
- `resources/views/components/layout.blade.php` — include `<x-impersonation-badge />`
- `resources/views/contributors/show.blade.php` — add impersonate button for admins
