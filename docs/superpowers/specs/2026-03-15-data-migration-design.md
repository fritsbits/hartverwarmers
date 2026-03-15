# Data Migration: Old Soulcenter DB → New Hartverwarmers

**Date:** 2026-03-15
**Status:** Approved

## Context

The old Hartverwarmers platform (Soulcenter) stores activities, users, comments, likes, and files in a different data structure. A partial import was done ~3 weeks ago: 394 fiches exist locally with hand-picked icons and initiative mappings. This migration completes the data by adding the migration link, importing real users, and bringing over comments, likes, and files.

### What exists locally (preserve all of this)
- 394 fiches with icons and initiative_id assignments
- 26 initiatives with images
- ~150 stub import-users (`@import.hartverwarmers.be` emails)
- 1 admin user (frederik.vincx@gmail.com)
- 9 seeded test members

### What the old DB has (to import)
- 395 published activities (maps 1:1 to fiches)
- 4,450 curated contacts (CSV with clean first/last names)
- 328 additional users who interacted (commented/liked) but aren't in the CSV
- 371 comments on activities
- 5,169 likes on activities
- 618 media files (downloads) for published activities
- 91 authors with company/org info
- Bcrypt password hashes (compatible, copy directly)

### Key constraint
**Never run destructive database commands.** All changes are additive (new columns, new rows) or targeted updates (re-linking user_id on specific fiches). No `migrate:fresh`.

### Prerequisites
- Old DB backup loaded into `soulcenter_backup` database (already done locally)
- Configure a `soulcenter` database connection in `config/database.php` pointing to `soulcenter_backup`
- Contacts CSV at `/Users/frederikvincx/Downloads/hartverwarmers/contacts-1773584301514.csv`
- Existing comments (5) and likes (15) in local DB are preserved; imports use INSERT with conflict guards

---

## Step 1: Add `migration_id` column to fiches

Add a nullable `bigint unsigned` column `migration_id` to the `fiches` table. This stores the old `activities.id` and is the key link for importing related data (comments, likes, files).

```
Schema: fiches.migration_id → old activities.id
```

Create a Laravel migration. Index the column for fast lookups. Add `migration_id` to the `$fillable` array on the `Fiche` model.

---

## Step 2: Establish title-based mapping and populate `migration_id`

### Matching strategy
1. **Exact title match** (case-insensitive, trimmed): matches 393 of 395 old activities
2. **Manual near-matches** for the 2 remaining:
   - Old `20377` "Enveloppenspel" → fiche `284` "Enveloppe spel"
   - Old `20381` "quiz  gezonde voeding" → fiche `396` "Quiz gezonde voeding"
3. **Duplicate title handling**: When multiple fiches share a title (e.g. "Muziekquiz" x3) and the old DB also has multiple activities with that title, match by `created_at` order (oldest-to-oldest).

### Implementation
Write an Artisan command `app:map-migration-ids` that:
1. Loads all old published activities (title + id + created_at)
2. Loads all fiches (title + id + created_at)
3. Groups by lowercase title
4. For unique titles: direct 1:1 match
5. For duplicate titles: sort both sides by created_at, match positionally
6. Handles the 2 manual near-matches
7. Updates `fiches.migration_id` for each match
8. Reports: matched count, unmatched fiches, unmatched activities

### Validation
- All 395 old activities should map to a fiche
- No fiche should have a migration_id that doesn't exist in old activities
- The 2 near-matches are handled

---

## Step 3: Import users

### Sources (in priority order, merged by email)

**Source A: 4,450 contacts from CSV** (`contacts-1773584301514.csv`)
- Fields: first_name, last_name, email (already cleaned)
- Password: look up by email in old DB `users` table, copy bcrypt hash
- For the 7 contacts not in old DB: generate a random bcrypt password (they'll need to use "forgot password")

**Source B: 328 additional interactors from old DB**
- Users who commented or liked activities but aren't in the CSV
- Split old `name` field into first_name/last_name (split on first space; if no space, put entire name in first_name)
- Copy bcrypt password from old DB

### Field mapping

| New field | Source |
|-----------|--------|
| first_name | CSV: `first_name` / Old DB: split from `name` |
| last_name | CSV: `last_name` / Old DB: split from `name` |
| email | CSV/Old DB: `email` |
| password | Old DB: `password` (bcrypt hash) |
| role | `member` (default) |
| organisation | NULL initially (updated in Step 5) |
| email_verified_at | Set to `now()` (they were active on old site) |
| created_at | From old DB `users.created_at` |

### Deduplication
- Key: lowercase email
- Skip any email that already exists in the new `users` table
- The existing admin (frederik.vincx@gmail.com) and seeded users are preserved

### Implementation
Artisan command `app:import-users` that:
1. Reads CSV contacts
2. Queries old DB for passwords and created_at by email match
3. Queries old DB for the 328 additional interactors
4. Inserts all users, skipping existing emails
5. Reports: imported count, skipped count, password-less count

---

## Step 4: Re-link fiches to real users

For fiches currently owned by stub import-users, re-link to the real imported user where possible.

### Linking chain
```
fiche.migration_id → old activity_id
→ activity_author_profile.activity_id → profile_id
→ authors.profile_id → author.email / author.user_id
→ old users.email → new users.email → new users.id
```

### Rules
1. If the author has a `user_id` in old DB → look up that user's email → find new user by email → update fiche.user_id
2. Else if the author has an `email` → find new user by email (first email if comma-separated) → update fiche.user_id
3. Else → keep the current stub import-user (shows correct author name)

### Expected results
- ~171 fiches re-linked to real users
- ~223 fiches keep their stub import-user (no author email available)

### Implementation
Artisan command `app:relink-fiche-authors` that:
1. For each fiche with a migration_id, looks up the author chain
2. Attempts to find a matching new user
3. Updates fiche.user_id if found
4. Reports: re-linked count, kept-stub count

---

## Step 5: Update import-user organisations

For stub import-users that remain as fiche owners, update their `organisation` field from the old author's `company` field.

### Linking chain
```
stub user → fiches owned by user → fiche.migration_id → old activity_id
→ activity_author_profile → authors → company
```

### Implementation
Part of the `app:relink-fiche-authors` command. For stub users (email ends with `@import.hartverwarmers.be`) that still own fiches, update `organisation` from the author's company.

---

## Step 6: Import comments

### Old schema
```
comments: id, commentable_type ('App\Models\Activity'), commentable_id, user_id, comment, created_at, deleted_at
```

### New schema
```
comments: id, commentable_type ('App\Models\Fiche'), commentable_id, user_id, body, created_at, deleted_at
```

### Mapping
- `commentable_id`: old activity_id → fiche_id via `migration_id` lookup
- `user_id`: old user_id → old user email → new user by email
- `comment` → `body` (field rename)
- `created_at`: preserve original timestamp
- `updated_at`: set equal to `created_at` (never edited)
- Skip soft-deleted comments (deleted_at IS NOT NULL)
- Old comments had no threading (`parent_id` not used) — all imported as top-level

### Edge cases
- Comment's old user not found in new DB → attribute to the generic import user (id=10 "Hartverwarmers Import")
- Comment's old `comment` field is NULL or empty → skip (new `body` column is NOT NULL)
- Comment's activity has no matching fiche → skip (log warning)

### Counts
- 371 comments to import
- Import into existing (mostly empty) comments table

### Implementation
Artisan command `app:import-comments`

---

## Step 7: Import likes

### Old schema
```
likes: id, user_id, likeable_id, likeable_type ('App\Models\Activity'), profile_id, created_at
```

### New schema
```
likes: id, user_id, session_id, likeable_type ('App\Models\Fiche'), likeable_id, type ('like'), count (1), created_at
```

### Mapping
- `likeable_id`: old activity_id → fiche_id via `migration_id`
- `user_id`: old user_id → new user by email match
- `type`: set to `'kudos'` (NOT `'like'` — the new system uses `'kudos'` for appreciation; `Fiche::kudos()` filters on this)
- `count`: set to `1`
- `session_id`: NULL (not applicable for imported likes)
- `created_at`: preserve original

### Edge cases
- Like's old user not in new DB → skip (anonymous likes without user aren't attributable)
- Duplicate check: unique constraint on (user_id, likeable_type, likeable_id, type) — skip if duplicate

### Counts
- 5,169 likes to import

### Implementation
Artisan command `app:import-likes`

### Step 7b: Recalculate `fiches.kudos_count`

After importing likes, the denormalized `kudos_count` column on fiches is stale. Recalculate:

```sql
UPDATE fiches SET kudos_count = (
    SELECT COALESCE(SUM(count), 0) FROM likes
    WHERE likeable_type = 'App\\Models\\Fiche'
    AND likeable_id = fiches.id AND type = 'kudos'
)
```

This runs as part of the `app:import-likes` command.

---

## Step 8: Import files

### Old system
Files are stored via Spatie Media Library:
- DB: `media` table with `model_id` (activity_id), `file_name`, `mime_type`, `size`, `collection_name`
- Disk: `storage/app/public/files/media/{media.id}/{file_name}`

### New system
- DB: `files` table with `fiche_id`, `original_filename`, `path`, `mime_type`, `size_bytes`, `sort_order`
- Disk: files stored at `path` relative to storage

### Mapping
Only import `collection_name = 'downloads'` for published activities (618 files).

| New field | Source |
|-----------|--------|
| fiche_id | old media.model_id → fiche via migration_id |
| original_filename | old media.file_name |
| path | `files/media/{old_media.id}/{old_media.file_name}` (files already on disk) |
| mime_type | old media.mime_type |
| size_bytes | old media.size |
| sort_order | old media.order_column (or 0) |

### File verification
Before creating the DB record, verify the file exists on disk. Log warnings for missing files.

### Implementation
Artisan command `app:import-files`

---

## Step 9: Cleanup media folders

After file import, identify media folders in `storage/app/public/files/media/` that:
- Don't correspond to any imported file record
- Aren't for published activities

These can be listed for manual review (don't auto-delete without user confirmation). Currently 4,827 folders exist; only 618 should be kept.

### Implementation
Artisan command `app:cleanup-media --dry-run` (list only by default, `--force` to actually delete)

---

## Command execution order

```bash
# 1. Migration
php artisan migrate

# 2. Map old activity IDs to fiches
php artisan app:map-migration-ids

# 3. Import users (contacts CSV + old DB interactors)
php artisan app:import-users

# 4. Re-link fiches to real users + update org names
php artisan app:relink-fiche-authors

# 5. Import comments
php artisan app:import-comments

# 6. Import likes
php artisan app:import-likes

# 7. Import files
php artisan app:import-files

# 8. Review media cleanup
php artisan app:cleanup-media --dry-run
```

Each command is idempotent (safe to re-run). Each reports what it did.

---

## Validation checklist

After running all commands:
- [ ] 394 fiches have a migration_id (1 old activity "Intergenerationeel" has no fiche — acceptable gap)
- [ ] ~4,778 new users imported (4,450 + 328)
- [ ] ~171 fiches re-linked to real users
- [ ] Stub users have updated organisation names
- [ ] 371 comments imported with correct fiche + user links
- [ ] ~5,169 likes imported
- [ ] 618 file records created, pointing to existing files on disk
- [ ] All existing icons and initiative mappings untouched
- [ ] Admin account (frederik.vincx@gmail.com) unchanged

---

## Risk mitigations

1. **No destructive operations** — all additive/update only
2. **Idempotent commands** — safe to re-run if something goes wrong
3. **Existing data untouched** — icons, initiatives, initiative mappings preserved
4. **Rollback path** — migration_id column can be dropped, imported users/comments/likes can be identified and deleted if needed (by created_at timestamp or import marker)
