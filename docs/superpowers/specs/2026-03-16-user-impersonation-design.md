# User Impersonation Design

## Problem

No way to see the platform as a specific user during manual browser testing or production support. Admin must log out and log in as another user, which is slow and loses context.

## Solution

Session-based user impersonation. Admins can "become" any user via two entry points, see the platform exactly as that user would, and switch back via a floating badge.

## Routes & Controller

### Routes (admin-only)

```
POST /admin/impersonate/{user}   → ImpersonateController@start
POST /admin/impersonate/stop     → ImpersonateController@stop
```

- `start` is protected by the existing `admin` middleware.
- `stop` is NOT behind `admin` middleware (since the logged-in user is the impersonated non-admin). Instead, it checks for `session('original_user_id')`.

### ImpersonateController

**`start(User $user)`**:
1. Reject if `$user->id === auth()->id()` (can't impersonate yourself)
2. Reject if `session('original_user_id')` already set (no nesting)
3. Reject if `$user->trashed()` (no soft-deleted users)
4. Store `session('original_user_id', auth()->id())`
5. `Auth::login($user)`
6. Redirect back with flash message

**`stop()`**:
1. Read `session('original_user_id')`
2. Abort 403 if not set
3. `Auth::login(User::findOrFail($originalId))`
4. `session()->forget('original_user_id')`
5. Redirect to `/admin/gebruikers` with flash message

## Middleware: HandleImpersonation

Registered globally in `bootstrap/app.php`.

Responsibilities:
- Share `is_impersonating` and `original_user_id` with all views via `View::share()`
- Block access to all `/admin/*` routes while impersonating, EXCEPT `POST /admin/impersonate/stop` — returns 403 with message explaining they must stop impersonating first

## Floating Badge

Blade component: `<x-impersonation-badge />`

Included in the main layout (`components/layout.blade.php`). Only renders when `session('original_user_id')` is set.

### Appearance
- Fixed bottom-left, positioned above the queue badge (`bottom: 90px; left: 12px`)
- Pill shape, orange/amber background (`#92400e` bg, white text) — warm, visible, non-alarming
- Shows: user avatar (small circle, or initials fallback) + full name + role badge + "Stop" button
- Inline styles matching the queue badge pattern
- `z-index: 9999`

### Stop button
- Small form inside the badge: `POST /admin/impersonate/stop` with CSRF token
- Styled as a small white/light button within the pill

## User Selection Entry Points

### a) Admin user list: `/admin/gebruikers`

New admin-only page (route name: `admin.users.index`).

- Simple table: avatar, name, email, role, organisation
- Each row has an "Impersonate" button (POST form) — not shown for the current admin's own row
- Protected by existing `admin` middleware
- Controller: `AdminUserController@index` (or similar)
- Basic search/filter not required for v1 but the page structure should allow it later

### b) Contributor profile pages: `/bijdragers/{slug}`

- When an admin views another user's profile, show a "Bekijk als deze gebruiker" button
- Only visible when `auth()->user()->isAdmin()` and the profile is NOT the admin's own
- Small secondary-style button, positioned near the profile header
- POST form to `/admin/impersonate/{user}`

## Security

| Rule | Implementation |
|------|---------------|
| Admin-only initiation | `admin` middleware on `start` route |
| No self-impersonation | Controller check: reject if target === self |
| No nesting | Controller check: reject if session key exists |
| No trashed users | Controller check: `$user->trashed()` |
| Session-scoped | Ends on logout or session expiry |
| Admin routes blocked | `HandleImpersonation` middleware blocks `/admin/*` except stop route |
| Stop always available | `stop` route checks session key, not admin role |

## Testing

### Feature Tests (`tests/Feature/ImpersonationTest.php`)

1. **Admin can start impersonation**: POST to start, assert session has `original_user_id`, assert auth user is target
2. **Admin can stop impersonation**: Start, then POST to stop, assert auth user is original admin, assert session key cleared
3. **Non-admin gets 403**: Contributor/member POSTing to start route gets 403
4. **Can't impersonate yourself**: Admin POSTing with own ID gets rejected
5. **Can't nest impersonation**: Already impersonating, POST to start again gets rejected
6. **Can't impersonate trashed user**: Soft-deleted user target gets rejected
7. **Admin routes blocked while impersonating**: GET `/admin/gebruikers` returns 403 while impersonating
8. **Stop route accessible while impersonating**: POST to stop succeeds even though auth user is non-admin
9. **Badge renders when impersonating**: Response contains impersonation badge markup when session key set
10. **Badge hidden when not impersonating**: Response does NOT contain badge markup normally

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
