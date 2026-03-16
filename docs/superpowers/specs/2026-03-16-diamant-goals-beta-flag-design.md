# DIAMANT Goals Beta Feature Flag

## Problem

The `diamant-goals` Pennant feature flag currently operates as a global on/off toggle. We need a temporary beta phase where only specific users (Maite Mallentjer + admins) can see the DIAMANT goals, while keeping the admin toggle as the mechanism to go live for everyone.

## Approach: Resolver checks global activation via DB, falls back to beta list

`activateForEveryone()` only updates existing rows — it won't activate the feature for users who've never had it resolved. To work around this, the resolver itself checks whether a global activation exists (the `__laravel_null` scope row in the `features` table). If it finds a stored `true`, it returns `true` for everyone. Otherwise, it falls back to the beta tester list.

### Lifecycle

1. **Beta phase:** No null-scope row exists (or it's `false`). Resolver returns `true` only for admins and Maite. Everyone else sees no goals.
2. **Go live:** Admin clicks "Inschakelen" → stores `true` for null scope via `Feature::for(null)->activate()`. Next time any user's feature is resolved, the resolver sees the global flag and returns `true`. Stored per-user values are purged so the resolver re-runs for everyone.
3. **Back to beta:** Admin clicks "Uitschakelen" → `Feature::purge()` removes all stored values (including the null-scope row) → resolver falls back to beta list.

## Changes

### 1. `app/Features/DiamantGoals.php`

Resolver checks global activation first, then falls back to beta list:

```php
private const ALLOWED_USER_IDS = [2623]; // Maite Mallentjer

public function resolve(?User $scope): bool
{
    // Check if globally activated via stored null-scope value
    $globallyActive = DB::table('features')
        ->where('name', 'diamant-goals')
        ->where('scope', '__laravel_null')
        ->where('value', 'true')
        ->exists();

    if ($globallyActive) {
        return true;
    }

    if (! $scope) {
        return false;
    }

    return $scope->isAdmin() || in_array($scope->id, self::ALLOWED_USER_IDS);
}
```

### 2. `app/Http/Controllers/FeatureController.php`

**`toggle()` method:**
- "Inschakelen": `Feature::for(null)->activate($class)` + `Feature::purge($class)` (purge per-user stored values so resolver re-runs with global flag active). Then re-store the null-scope activation since purge clears it too.
- "Uitschakelen": `Feature::purge($class)` — removes all stored values including the null-scope row, returning to beta.

**`index()` method:** Query the `features` table directly for the null-scope stored `true` to determine badge state (avoids triggering Pennant's resolve-and-store cycle).

### 3. `resources/views/admin/features.blade.php`

Show "Beta" badge (yellow) when the feature is not globally active but the admin can see it (resolver mode). Show "Actief" (green) when globally active.

### 4. Production deploy

Add `php artisan pennant:purge diamant-goals` to the deploy script (one-time) to clear stale stored values so the new resolver takes effect.

## Route & View Behavior

No changes needed to routes or Blade views:

- **`EnsureFeaturesAreActive` middleware** on `/doelen` routes: resolves against authenticated user. Guests get 404 during beta. After global activation, resolver returns `true` for all scopes including null (guests).
- **`@feature('diamant-goals')` Blade directive**: same behavior.
- **`Feature::active()` in Livewire**: same behavior.

## Admin Page UX

| State | Badge | Button | What happens on click |
|-------|-------|--------|-----------------------|
| Beta (resolver mode) | Beta (yellow) | Inschakelen | Activate null scope + purge per-user values → live for all |
| Live (global active) | Actief (green) | Uitschakelen | `purge()` → back to beta |

## Testing

Existing `FeatureFlagTest` tests use `Feature::define()` to override behavior per test, so they remain valid. Add tests for the beta/live lifecycle:

1. Non-admin user cannot see goals during beta
2. Admin and Maite can see goals during beta
3. After global activation, regular users can see goals
4. After purge, regular users lose access, admins retain it
