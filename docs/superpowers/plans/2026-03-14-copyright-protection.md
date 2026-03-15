# Copyright Protection & Upload Policy Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement technical copyright protection measures: disclaimer checkbox with audit logging, EXIF stripping on served files, and a notice-and-takedown page.

**Architecture:** Three independent subsystems: (1) Upload audit logging via new `file_uploads` table + disclaimer acceptance in FicheWizard, (2) Static `/auteursrecht` page with notice-and-takedown instructions (DSA Article 16), (3) Updated gebruiksvoorwaarden with copyright-specific clauses. Each subsystem is independently testable and deployable.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI Pro, PHPUnit, Imagick (EXIF stripping), SHA-256 hashing

---

## Scope

This plan covers the **technical implementation items** from the Notion spec:
- Disclaimer checkbox at upload with `disclaimer_accepted_at` logging
- Audit logging per upload (`user_id`, `ip_address`, `file_hash`, `disclaimer_accepted_at`, `original_filename`)
- Notice-and-takedown page (`/auteursrecht`)
- ToS update: garantieclausule, vrijwaringsclausule, verwijderingsrechten, recidivistenbeleid

**Not in scope** (juridisch / external / "Dit doen we niet"): recidivistentracking/suspension (technisch), content behind auth, VZW structure, insurance, IP lawyer, TinEye API.

---

## File Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `database/migrations/2026_03_14_*_create_file_uploads_table.php` | Audit log table |
| Create | `app/Models/FileUpload.php` | Audit log model |
| Create | `database/factories/FileUploadFactory.php` | Test factory |
| Modify | `app/Livewire/FicheWizard.php` | Disclaimer checkbox + audit log creation |
| Modify | `resources/views/livewire/fiche-wizard.blade.php` | Disclaimer checkbox UI (step 1) |
| Modify | `routes/web.php` | Add `/auteursrecht` route |
| Create | `resources/views/legal/copyright.blade.php` | Notice-and-takedown page |
| Modify | `resources/views/legal/terms.blade.php` | Add copyright-specific clauses to ToS |
| Create | `tests/Feature/FileUploadAuditTest.php` | Tests for audit logging + disclaimer |
| Create | `tests/Feature/CopyrightPageTest.php` | Tests for copyright page |
| Create | `tests/Feature/TermsOfServiceTest.php` | Tests for updated ToS content |

---

## Chunk 1: Upload Audit Logging & Disclaimer Checkbox

### Task 1: Create `file_uploads` audit log table

**Files:**
- Create: `database/migrations/2026_03_14_000001_create_file_uploads_table.php`
- Create: `app/Models/FileUpload.php`
- Create: `database/factories/FileUploadFactory.php`

- [ ] **Step 1: Create migration via Artisan**

```bash
php artisan make:migration create_file_uploads_table --no-interaction
```

- [ ] **Step 2: Write migration schema**

Edit the generated migration file:

```php
public function up(): void
{
    Schema::create('file_uploads', function (Blueprint $table) {
        $table->id();
        $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->string('ip_address', 45);
        $table->string('file_hash', 64)->comment('SHA-256 hash of uploaded file');
        $table->string('original_filename');
        $table->timestamp('disclaimer_accepted_at');
        $table->timestamps();

        $table->index('user_id');
        $table->index('file_hash');
    });
}

public function down(): void
{
    Schema::dropIfExists('file_uploads');
}
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate
```

Expected: "Running migrations... create_file_uploads_table ... DONE"

- [ ] **Step 4: Create FileUpload model**

```bash
php artisan make:model FileUpload --no-interaction
```

Then replace contents with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'ip_address',
        'file_hash',
        'original_filename',
        'disclaimer_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'disclaimer_accepted_at' => 'datetime',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Create FileUpload factory**

```bash
php artisan make:factory FileUploadFactory --no-interaction
```

Then replace contents with:

```php
<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileUpload>
 */
class FileUploadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'file_id' => File::factory(),
            'user_id' => User::factory(),
            'ip_address' => fake()->ipv4(),
            'file_hash' => hash('sha256', fake()->uuid()),
            'original_filename' => fake()->word() . '.pdf',
            'disclaimer_accepted_at' => now(),
        ];
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add database/migrations/*file_uploads* app/Models/FileUpload.php database/factories/FileUploadFactory.php
git commit -m "feat: add file_uploads audit log table and model"
```

---

### Task 2: Add disclaimer checkbox to FicheWizard

**Files:**
- Create: `tests/Feature/FileUploadAuditTest.php`
- Modify: `app/Livewire/FicheWizard.php`
- Modify: `resources/views/livewire/fiche-wizard.blade.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/FileUploadAuditTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\FicheWizard;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FileUploadAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_step1_requires_disclaimer_when_files_uploaded(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')])
            ->call('submitStep1')
            ->assertHasErrors('disclaimerAccepted')
            ->assertSet('currentStep', 1);
    }

    public function test_step1_proceeds_when_disclaimer_accepted(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')])
            ->set('disclaimerAccepted', true)
            ->call('submitStep1')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 2);
    }

    public function test_upload_creates_audit_record(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')]);

        $this->assertDatabaseCount('file_uploads', 1);
        $audit = FileUpload::first();
        $this->assertEquals($user->id, $audit->user_id);
        $this->assertEquals('test.pdf', $audit->original_filename);
        $this->assertNotNull($audit->file_hash);
        $this->assertNotNull($audit->ip_address);
    }

    public function test_disclaimer_backfills_accepted_at_on_submit(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')])
            ->set('disclaimerAccepted', true)
            ->call('submitStep1');

        $audit = FileUpload::first();
        $this->assertNotNull($audit->disclaimer_accepted_at);
    }

    public function test_audit_record_contains_sha256_hash(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [UploadedFile::fake()->create('test.pdf', 100, 'application/pdf')]);

        $audit = FileUpload::first();
        $this->assertEquals(64, strlen($audit->file_hash));
    }

    public function test_multiple_uploads_create_multiple_audit_records(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FicheWizard::class)
            ->set('uploads', [
                UploadedFile::fake()->create('file1.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('file2.pdf', 200, 'application/pdf'),
            ]);

        $this->assertDatabaseCount('file_uploads', 2);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact tests/Feature/FileUploadAuditTest.php
```

Expected: FAIL — `disclaimerAccepted` property doesn't exist yet.

- [ ] **Step 3: Add disclaimer property and validation to FicheWizard**

In `app/Livewire/FicheWizard.php`, add the import at top:

```php
use App\Models\FileUpload;
```

Add property after `public array $uploads = [];` (around line 55):

```php
#[Session(key: 'fiche-wizard.disclaimerAccepted')]
public bool $disclaimerAccepted = false;
```

- [ ] **Step 4: Add audit logging to `updatedUploads()` and disclaimer validation to `submitStep1()`**

**Audit logging:** In `app/Livewire/FicheWizard.php`, inside the `foreach ($this->uploads as $upload)` loop in `updatedUploads()`, after the `File::create(...)` call and the `$this->uploadedFiles[]` push (around line 268), add audit logging:

```php
FileUpload::create([
    'file_id' => $file->id,
    'user_id' => auth()->id(),
    'ip_address' => request()->ip(),
    'file_hash' => hash_file('sha256', $upload->getRealPath()),
    'original_filename' => $upload->getClientOriginalName(),
    'disclaimer_accepted_at' => $this->disclaimerAccepted ? now() : null,
]);
```

**Disclaimer validation:** In `submitStep1()`, add validation before advancing to step 2. Files can be uploaded without the checkbox, but the user cannot proceed to step 2 without accepting the disclaimer (if they have files). Replace the `submitStep1()` method:

```php
public function submitStep1(): void
{
    if (! empty($this->uploadedFiles) && ! $this->disclaimerAccepted) {
        $this->addError('disclaimerAccepted', 'Je moet bevestigen dat je de rechten hebt om deze bestanden te delen.');

        return;
    }

    // Backfill disclaimer_accepted_at on audit records created before checkbox was ticked
    if ($this->disclaimerAccepted && ! empty($this->uploadedFiles)) {
        $fileIds = collect($this->uploadedFiles)->pluck('id')->toArray();
        FileUpload::whereIn('file_id', $fileIds)
            ->whereNull('disclaimer_accepted_at')
            ->update(['disclaimer_accepted_at' => now()]);
    }

    $this->findSimilarFiches();
    $this->currentStep = 2;
}
```

This way the UX flow is: upload files → checkbox appears → user ticks it → clicks "Verder" → validation passes. Files upload immediately (audit record created), disclaimer is validated on step transition.

- [ ] **Step 5: Add disclaimer to `clearWizardSession()`**

In the `clearWizardSession()` method, add:

```php
$this->disclaimerAccepted = false;
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
php artisan test --compact tests/Feature/FileUploadAuditTest.php
```

Expected: 4 tests pass.

- [ ] **Step 7: Add disclaimer checkbox to the wizard blade (step 1)**

In `resources/views/livewire/fiche-wizard.blade.php`, find the upload dropzone area in Step 1. Add the disclaimer checkbox **before the "Verder" button** at the bottom of step 1's content. Look for the submit button section (the `submitStep1` button area) and add above it:

```blade
{{-- Disclaimer checkbox (only shown when files have been uploaded) --}}
@if(count($uploadedFiles) > 0)
    <flux:field>
        <label class="flex items-start gap-3 cursor-pointer">
            <flux:checkbox wire:model.live="disclaimerAccepted" />
            <span class="text-sm text-[var(--color-text-secondary)] leading-snug">
                Ik bevestig dat ik de rechten heb om deze bestanden te delen, of dat ik toestemming heb van de rechthebbende.
            </span>
        </label>
        @error('disclaimerAccepted')
            <flux:error>{{ $message }}</flux:error>
        @enderror
    </flux:field>
@endif
```

**UX flow:** Files upload immediately (dropzone works without checkbox). Once files appear in the list, the disclaimer checkbox shows below them. User must tick it before "Verder" (step 1 submit) — validation happens in `submitStep1()`, not on upload. This avoids the UX problem of rejecting uploads before the checkbox is visible.

- [ ] **Step 8: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add app/Livewire/FicheWizard.php resources/views/livewire/fiche-wizard.blade.php tests/Feature/FileUploadAuditTest.php
git commit -m "feat: add disclaimer checkbox and audit logging for file uploads"
```

---



## Chunk 2: Notice-and-Takedown Page

### Task 4: Create `/auteursrecht` copyright page

**Files:**
- Create: `resources/views/legal/copyright.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/CopyrightPageTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CopyrightPageTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class CopyrightPageTest extends TestCase
{
    public function test_copyright_page_is_accessible(): void
    {
        $this->get('/auteursrecht')
            ->assertStatus(200)
            ->assertSee('Auteursrecht');
    }

    public function test_copyright_page_has_required_dsa_content(): void
    {
        $this->get('/auteursrecht')
            ->assertSee('info@hartverwarmers.be')
            ->assertSee('notice-and-takedown');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact tests/Feature/CopyrightPageTest.php
```

Expected: FAIL — 404

- [ ] **Step 3: Add route**

In `routes/web.php`, add after the existing legal routes (after line 100):

```php
Route::view('/auteursrecht', 'legal.copyright')->name('legal.copyright');
```

- [ ] **Step 4: Create the copyright page view**

Create `resources/views/legal/copyright.blade.php`. Use the same layout as `legal/terms.blade.php` and `legal/privacy.blade.php`. The page must contain (DSA Article 16 requirements):

- How to report copyright infringement (notice-and-takedown procedure)
- What information to include in a notice
- Contact email `info@hartverwarmers.be`
- What happens after a notice is received
- Counter-notice procedure

Content should be in Dutch, following the platform's warm tone. Reference the existing terms (section 4 on content & license, section 8 on moderation).

```blade
<x-layout title="Auteursrecht — Hartverwarmers" description="Notice-and-takedown procedure voor auteursrechtclaims op Hartverwarmers.">
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-3xl mx-auto px-6 py-12">
            <span class="section-label section-label-hero">Juridisch</span>
            <h1 class="text-4xl mt-1">Auteursrecht & notice-and-takedown</h1>
            <p class="text-lg text-[var(--color-text-secondary)] mt-3">Hoe we omgaan met auteursrechtelijk beschermd materiaal op ons platform.</p>
        </div>
    </section>

    <section class="max-w-3xl mx-auto px-6 py-12 prose prose-lg">
        <h2>Ons beleid</h2>
        <p>Hartverwarmers respecteert de intellectuele eigendomsrechten van anderen. Gebruikers die content delen op ons platform bevestigen dat zij de rechten hebben om die content te delen, of dat zij toestemming hebben van de rechthebbende.</p>
        <p>We handelen als hostingprovider onder de <strong>Digital Services Act (DSA)</strong> en volgen een notice-and-takedown procedure bij meldingen van auteursrechtinbreuken.</p>

        <h2>Een inbreuk melden (notice-and-takedown)</h2>
        <p>Als je meent dat materiaal op Hartverwarmers inbreuk maakt op jouw auteursrecht, stuur dan een melding naar <a href="mailto:info@hartverwarmers.be">info@hartverwarmers.be</a> met de volgende gegevens:</p>
        <ol>
            <li>Je naam en contactgegevens</li>
            <li>Een beschrijving van het beschermde werk waarop inbreuk wordt gemaakt</li>
            <li>De exacte URL of locatie van het inbreukmakende materiaal op ons platform</li>
            <li>Een verklaring dat je te goeder trouw handelt en dat het gebruik van het materiaal niet is toegestaan door de rechthebbende</li>
            <li>Je handtekening (fysiek of elektronisch)</li>
        </ol>

        <h2>Wat gebeurt er na je melding?</h2>
        <ol>
            <li>We bevestigen de ontvangst van je melding binnen 5 werkdagen</li>
            <li>We beoordelen de melding en verwijderen het materiaal als de melding gegrond lijkt</li>
            <li>We informeren de gebruiker die het materiaal heeft geüpload over de verwijdering</li>
            <li>Bij herhaaldelijke overtredingen kunnen we het account van de gebruiker schorsen</li>
        </ol>

        <h2>Tegenmelding (counter-notice)</h2>
        <p>Als je content is verwijderd en je meent dat dit ten onrechte is gebeurd, kun je een tegenmelding indienen bij <a href="mailto:info@hartverwarmers.be">info@hartverwarmers.be</a>. Vermeld daarin:</p>
        <ol>
            <li>Je naam en contactgegevens</li>
            <li>Een beschrijving van het verwijderde materiaal</li>
            <li>Een verklaring waarom je meent dat de verwijdering onterecht was</li>
        </ol>

        <h2>Contact</h2>
        <p>Voor vragen over auteursrecht op ons platform, neem contact op via <a href="mailto:info@hartverwarmers.be">info@hartverwarmers.be</a>.</p>

        <p class="text-sm text-[var(--color-text-secondary)]">Laatst bijgewerkt: maart 2026</p>
    </section>
</x-layout>
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test --compact tests/Feature/CopyrightPageTest.php
```

Expected: 2 tests pass.

- [ ] **Step 6: Add copyright page link to footer**

Check the footer component for existing legal links (privacy, terms). Add a link to the copyright page in the same section:

```blade
<a href="{{ route('legal.copyright') }}" class="...">Auteursrecht</a>
```

(Use the same classes as the existing privacy/terms links.)

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add resources/views/legal/copyright.blade.php routes/web.php tests/Feature/CopyrightPageTest.php
git commit -m "feat: add /auteursrecht notice-and-takedown page (DSA Article 16)"
```

---

## Chunk 3: Terms of Service Update

### Task 5: Add copyright-specific clauses to gebruiksvoorwaarden

The current ToS (section 4) has a basic content ownership + license clause. The Notion spec requires strengthening with: **garantieclausule** (warranty), **vrijwaringsclausule** (indemnification), **verwijderingsrechten** (removal rights), and **recidivistenbeleid** (repeat offender policy).

**Files:**
- Modify: `resources/views/legal/terms.blade.php`
- Create: `tests/Feature/TermsOfServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TermsOfServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class TermsOfServiceTest extends TestCase
{
    public function test_terms_page_contains_warranty_clause(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('garandeert')
            ->assertSee('geen inbreuk');
    }

    public function test_terms_page_contains_indemnification_clause(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('vrijwaart');
    }

    public function test_terms_page_contains_removal_rights(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('auteursrechtinbreuk')
            ->assertSee('verwijderen');
    }

    public function test_terms_page_contains_repeat_offender_policy(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('herhaaldelijke');
    }

    public function test_terms_page_links_to_copyright_page(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee(route('legal.copyright'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact tests/Feature/TermsOfServiceTest.php
```

Expected: FAIL — new clauses not yet present.

- [ ] **Step 3: Update section 4 (Inhoud en licentie) with warranty clause**

In `resources/views/legal/terms.blade.php`, in section 4, after the existing bullet "Bevestig je dat de bijdrage je eigen werk is, of dat je toestemming hebt om het te delen.", add a stronger warranty bullet:

```blade
<li><strong class="text-[var(--color-text-primary)]">Garandeert</strong> je dat je bijdrage geen inbreuk maakt op het auteursrecht, merkrecht of enig ander intellectueel eigendomsrecht van derden.</li>
```

- [ ] **Step 4: Add new section 4b — Vrijwaring (indemnification)**

After section 4, add a new subsection. Insert after the closing `</div>` of section 4 and before section 5:

```blade
{{-- 4b. Vrijwaring --}}
<div>
    <h2 class="text-xl mb-3">4b. Vrijwaring bij auteursrechtclaims</h2>
    <p class="text-[var(--color-text-secondary)] mb-3">Als gebruiker <strong class="text-[var(--color-text-primary)]">vrijwaart</strong> je de beheerder tegen alle claims, kosten en schade die voortvloeien uit een bewering dat jouw bijdrage inbreuk maakt op de intellectuele eigendomsrechten van een derde.</p>
    <p class="text-[var(--color-text-secondary)]">Dit betekent dat als een derde partij een claim indient met betrekking tot content die jij hebt geupload, jij verantwoordelijk bent voor de verdediging en eventuele kosten, en niet het platform.</p>
</div>
```

- [ ] **Step 5: Update section 8 (Moderatie) with copyright-specific removal rights and repeat offender policy**

In section 8, replace the current content with strengthened language:

```blade
{{-- 8. Moderatie --}}
<div>
    <h2 class="text-xl mb-3">8. Moderatie en auteursrechtbeleid</h2>
    <p class="text-[var(--color-text-secondary)] mb-3">De beheerder behoudt het recht om:</p>
    <ul class="list-disc list-inside space-y-1 text-[var(--color-text-secondary)]">
        <li>Bijdragen onmiddellijk te verwijderen bij een gegronde melding van auteursrechtinbreuk, conform onze <a href="{{ route('legal.copyright') }}" class="text-[var(--color-primary)] hover:underline">notice-and-takedown procedure</a>.</li>
        <li>Bijdragen te verwijderen of aan te passen die in strijd zijn met deze voorwaarden.</li>
        <li>Accounts te waarschuwen, op te schorten of te verwijderen bij herhaaldelijke of ernstige overtredingen.</li>
    </ul>
    <p class="text-[var(--color-text-secondary)] mt-3"><strong class="text-[var(--color-text-primary)]">Herhaaldelijke overtredingen:</strong> Bij herhaaldelijke auteursrechtinbreuken kan het account van de gebruiker tijdelijk of permanent worden geschorst. De beheerder bepaalt of er sprake is van een herhaaldelijke overtreding.</p>
    <p class="text-[var(--color-text-secondary)] mt-3">Gebruikers kunnen ongepaste inhoud of vermoedelijke auteursrechtinbreuken melden via <a href="mailto:info@hartverwarmers.be" class="text-[var(--color-primary)] hover:underline">info@hartverwarmers.be</a>.</p>
</div>
```

- [ ] **Step 6: Update the "Laatst bijgewerkt" date**

Change the date at the top of the terms page from "7 maart 2026" to "14 maart 2026":

```blade
<p class="text-meta text-sm mt-4">Laatst bijgewerkt: 14 maart 2026</p>
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
php artisan test --compact tests/Feature/TermsOfServiceTest.php
```

Expected: 5 tests pass.

- [ ] **Step 8: Commit**

```bash
git add resources/views/legal/terms.blade.php tests/Feature/TermsOfServiceTest.php
git commit -m "feat: add copyright warranty, indemnification, and repeat offender policy to ToS"
```

---

## Chunk 4: Final Integration & Verification

### Task 6: Run full test suite and verify

- [ ] **Step 1: Run existing FicheWizardTest to ensure no regressions**

```bash
php artisan test --compact tests/Feature/FicheWizardTest.php
```

Expected: All existing tests pass. Some may need updating if they upload files without setting `disclaimerAccepted` — update those tests to include `->set('disclaimerAccepted', true)` before uploading.

- [ ] **Step 2: Fix any broken existing tests**

If existing `FicheWizardTest` tests fail because they upload files without the disclaimer, add `->set('disclaimerAccepted', true)` to those test cases before the upload step. The tests that upload files are likely:
- Tests that call `->set('uploads', [...])` — add `->set('disclaimerAccepted', true)` before them.

- [ ] **Step 3: Run all new tests together**

```bash
php artisan test --compact tests/Feature/FileUploadAuditTest.php tests/Feature/CopyrightPageTest.php tests/Feature/TermsOfServiceTest.php
```

Expected: All pass.

- [ ] **Step 4: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass.

- [ ] **Step 5: Build frontend assets**

```bash
npm run build
```

- [ ] **Step 6: Take screenshots to verify UI**

Use the project screenshot helper to verify:

```bash
node scripts/screenshot.cjs /auteursrecht /tmp/auteursrecht.png
```

Verify the copyright page looks correct.

- [ ] **Step 7: Final commit (if any fixes needed)**

Stage only the specific test files that were updated:

```bash
git add tests/Feature/FicheWizardTest.php
git commit -m "fix: update existing tests for disclaimer requirement"
```
