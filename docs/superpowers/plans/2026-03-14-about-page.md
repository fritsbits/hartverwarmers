# About Page Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `/over-ons` about page with 6 narrative content blocks, dynamic stats, an inline Livewire contact form, and a Web Share button.

**Architecture:** Static Blade view with a View Composer for cached stats and one Livewire island for the progressive-disclosure contact form. No controller needed — existing `Route::view` stays. First Mailable in the project for support messages.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI Pro, Alpine.js (x-collapse), Tailwind CSS v4

**Spec:** `docs/superpowers/specs/2026-03-14-about-page-design.md`

---

## Chunk 1: Infrastructure (Stats, Mail, Assets)

### Task 1: Download and place image assets

**Files:**
- Add: `public/img/covers/hartverwarmers.jpg`
- Add: `public/img/about/lancering-activiteit.jpg`
- Add: `public/img/about/lancering-boek.jpg`

- [ ] **Step 1: Create directory and download book cover**

```bash
mkdir -p public/img/about
curl -L -o public/img/covers/hartverwarmers.jpg "https://cdn.standaardboekhandel.be/product/9782509037831/front-medium-3383127254.jpg"
```

- [ ] **Step 2: Copy lancering photos from Desktop**

```bash
cp "/Users/frederikvincx/Desktop/Screenshot 2026-03-14 at 21.17.14.png" public/img/about/lancering-activiteit.jpg
cp "/Users/frederikvincx/Desktop/Screenshot 2026-03-14 at 21.16.26.png" public/img/about/lancering-boek.jpg
```

- [ ] **Step 3: Verify files exist**

```bash
ls -la public/img/about/ public/img/covers/hartverwarmers.jpg
```

- [ ] **Step 4: Commit**

```bash
git add public/img/about/ public/img/covers/hartverwarmers.jpg
git commit -m "assets: add about page images (book cover + lancering photos)"
```

---

### Task 2: Add mail support address config

**Files:**
- Modify: `config/mail.php:117` — add `support_address` key after `from` block
- Modify: `.env.example:54` — add `SUPPORT_ADDRESS`

- [ ] **Step 1: Add support_address to config/mail.php**

Add after the `'from'` array (after line 116):

```php
    'support_address' => env('SUPPORT_ADDRESS', 'frederik.vincx@gmail.com'),
```

- [ ] **Step 2: Add SUPPORT_ADDRESS to .env.example**

Add after `RESEND_API_KEY=` (line 54):

```
SUPPORT_ADDRESS=frederik.vincx@gmail.com
```

- [ ] **Step 3: Commit**

```bash
git add config/mail.php .env.example
git commit -m "config: add support email address for about page contact form"
```

---

### Task 3: Create AboutComposer for dynamic stats

**Files:**
- Create: `app/View/Composers/AboutComposer.php`
- Modify: `app/Providers/AppServiceProvider.php:39` — register composer
- Modify: `tests/Feature/AboutPageTest.php` — add stats test

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/AboutPageTest.php`:

```php
use App\Models\Fiche;
use App\Models\User;

public function test_about_page_shows_dynamic_stats(): void
{
    $user = User::factory()->create(['organisation' => 'WZC Test']);
    Fiche::factory()->for($user)->published()->create();

    $response = $this->get('/over-ons');

    $response->assertOk();
    $response->assertViewHas('aboutStats');
    $data = $response->viewData('aboutStats');
    $this->assertArrayHasKey('fiches_count', $data);
    $this->assertArrayHasKey('contributors_count', $data);
    $this->assertArrayHasKey('users_count', $data);
    $this->assertGreaterThan(0, $data['fiches_count']);
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=test_about_page_shows_dynamic_stats
```

Expected: FAIL — `aboutStats` view data not present.

- [ ] **Step 3: Create AboutComposer**

Create `app/View/Composers/AboutComposer.php`:

```php
<?php

namespace App\View\Composers;

use App\Models\Fiche;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AboutComposer
{
    public function compose(View $view): void
    {
        $stats = Cache::remember('about_stats', 3600, function () {
            return [
                'fiches_count' => Fiche::count(),
                'contributors_count' => User::whereHas('fiches')->count(),
                'users_count' => User::count(),
            ];
        });

        $view->with('aboutStats', $stats);
    }
}
```

- [ ] **Step 4: Register in AppServiceProvider**

Add after line 39 in `app/Providers/AppServiceProvider.php` (after the FooterComposer line):

```php
View::composer('about', AboutComposer::class);
```

Add the import at the top:

```php
use App\View\Composers\AboutComposer;
```

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --compact --filter=test_about_page_shows_dynamic_stats
```

Expected: PASS

- [ ] **Step 6: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/View/Composers/AboutComposer.php app/Providers/AppServiceProvider.php tests/Feature/AboutPageTest.php
git commit -m "feat: add AboutComposer for dynamic stats on about page"
```

---

### Task 4: Create SupportMessage Mailable

**Files:**
- Create: `app/Mail/SupportMessage.php`
- Create: `resources/views/mail/support-message.blade.php`
- Modify: `tests/Feature/AboutPageTest.php` — add mailable test

- [ ] **Step 1: Generate mailable with artisan**

```bash
php artisan make:mail SupportMessage --no-interaction
```

- [ ] **Step 2: Write the failing test**

Add to `tests/Feature/AboutPageTest.php`:

```php
use App\Mail\SupportMessage;
use Illuminate\Support\Facades\Mail;

public function test_support_message_mailable_has_correct_envelope(): void
{
    $mailable = new SupportMessage(
        senderName: 'Jan Janssen',
        senderEmail: 'jan@example.com',
        senderMessage: 'Ik wil graag bijdragen.',
    );

    $mailable->assertHasSubject('Steunbericht via Hartverwarmers — Jan Janssen');
    $mailable->assertTo(config('mail.support_address'));
    $mailable->assertHasReplyTo('jan@example.com');
}
```

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --compact --filter=test_support_message_mailable_has_correct_envelope
```

Expected: FAIL

- [ ] **Step 4: Implement SupportMessage mailable**

Replace `app/Mail/SupportMessage.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $senderMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [config('mail.support_address')],
            replyTo: [$this->senderEmail],
            subject: "Steunbericht via Hartverwarmers — {$this->senderName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.support-message',
        );
    }
}
```

- [ ] **Step 5: Create email template**

Create `resources/views/mail/support-message.blade.php`:

```blade
<x-mail::message>
# Steunbericht van {{ $senderName }}

**Van:** {{ $senderName }} ({{ $senderEmail }})

---

{{ $senderMessage }}

---

*Dit bericht is verstuurd via het contactformulier op de over-pagina van Hartverwarmers.*
</x-mail::message>
```

- [ ] **Step 6: Run test to verify it passes**

```bash
php artisan test --compact --filter=test_support_message_mailable_has_correct_envelope
```

Expected: PASS

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Mail/SupportMessage.php resources/views/mail/support-message.blade.php tests/Feature/AboutPageTest.php
git commit -m "feat: add SupportMessage mailable for about page contact form"
```

---

### Task 5: Create SupportContactForm Livewire component

**Files:**
- Create: `app/Livewire/SupportContactForm.php`
- Create: `resources/views/livewire/support-contact-form.blade.php`
- Modify: `tests/Feature/AboutPageTest.php` — add form tests

- [ ] **Step 1: Generate Livewire component**

```bash
php artisan make:livewire SupportContactForm --no-interaction
```

- [ ] **Step 2: Write the failing tests**

Add to `tests/Feature/AboutPageTest.php`:

```php
use Livewire\Livewire;
use App\Livewire\SupportContactForm;

public function test_support_form_validates_required_fields(): void
{
    Livewire::test(SupportContactForm::class)
        ->call('send')
        ->assertHasErrors(['name' => 'required', 'email' => 'required', 'message' => 'required']);
}

public function test_support_form_validates_email_format(): void
{
    Livewire::test(SupportContactForm::class)
        ->set('name', 'Jan')
        ->set('email', 'not-an-email')
        ->set('message', 'Test bericht')
        ->call('send')
        ->assertHasErrors(['email' => 'email']);
}

public function test_support_form_sends_email(): void
{
    Mail::fake();

    Livewire::test(SupportContactForm::class)
        ->set('name', 'Jan Janssen')
        ->set('email', 'jan@example.com')
        ->set('message', 'Ik wil graag bijdragen aan het platform.')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    Mail::assertQueued(SupportMessage::class, function (SupportMessage $mail) {
        return $mail->senderName === 'Jan Janssen'
            && $mail->senderEmail === 'jan@example.com'
            && $mail->hasTo(config('mail.support_address'));
    });
}

public function test_support_form_is_rate_limited(): void
{
    Mail::fake();

    $component = Livewire::test(SupportContactForm::class);

    for ($i = 0; $i < 3; $i++) {
        $component
            ->set('name', 'Jan')
            ->set('email', 'jan@example.com')
            ->set('message', "Bericht $i")
            ->call('send')
            ->assertHasNoErrors();

        // Reset sent state to allow resending
        $component->set('sent', false);
    }

    // 4th attempt should be throttled
    $component
        ->set('name', 'Jan')
        ->set('email', 'jan@example.com')
        ->set('message', 'Nog een bericht')
        ->call('send')
        ->assertHasErrors(['throttle']);

    Mail::assertQueued(SupportMessage::class, 3);
}
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --compact --filter=test_support_form
```

Expected: FAIL

- [ ] **Step 4: Implement SupportContactForm component**

Replace `app/Livewire/SupportContactForm.php`:

```php
<?php

namespace App\Livewire;

use App\Mail\SupportMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SupportContactForm extends Component
{
    #[Validate('required', message: ['required' => 'Vul je naam in.'])]
    public string $name = '';

    #[Validate('required|email', message: ['required' => 'Vul je e-mailadres in.', 'email' => 'Vul een geldig e-mailadres in.'])]
    public string $email = '';

    #[Validate('required|max:2000', message: ['required' => 'Schrijf een kort bericht.', 'max' => 'Je bericht mag maximaal 2000 tekens bevatten.'])]
    public string $message = '';

    public bool $sent = false;

    public function send(): void
    {
        $key = 'support-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('throttle', 'Je hebt te veel berichten verstuurd. Probeer het later opnieuw.');

            return;
        }

        $this->validate();

        RateLimiter::hit($key, 600);

        Mail::send(new SupportMessage(
            senderName: $this->name,
            senderEmail: $this->email,
            senderMessage: $this->message,
        ));

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.support-contact-form');
    }
}
```

- [ ] **Step 5: Implement the Blade template**

Replace `resources/views/livewire/support-contact-form.blade.php`:

```blade
<div>
    @if ($sent)
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
            <p class="text-lg font-semibold text-green-800">Bedankt voor je bericht!</p>
            <p class="text-green-700 mt-1">Frederik neemt zo snel mogelijk contact met je op.</p>
        </div>
    @else
        <form wire:submit="send" class="space-y-4 mt-6">
            @error('throttle')
                <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-red-700 text-sm">{{ $message }}</p>
                </div>
            @enderror

            <flux:field>
                <flux:label>Naam</flux:label>
                <flux:input wire:model="name" placeholder="Je naam" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>E-mailadres</flux:label>
                <flux:input wire:model="email" type="email" placeholder="je@email.be" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Bericht</flux:label>
                <flux:textarea wire:model="message" placeholder="Hoe wil je Hartverwarmers steunen?" rows="4" />
                <flux:error name="message" />
            </flux:field>

            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Verstuur bericht</span>
                <span wire:loading>Bezig met versturen...</span>
            </flux:button>
        </form>
    @endif
</div>
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
php artisan test --compact --filter=test_support_form
```

Expected: All 4 tests PASS

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/SupportContactForm.php resources/views/livewire/support-contact-form.blade.php tests/Feature/AboutPageTest.php
git commit -m "feat: add SupportContactForm Livewire component with rate limiting"
```

---

## Chunk 2: Page View Implementation

### Task 6: Implement the about page Blade view

**Files:**
- Modify: `resources/views/about.blade.php` — full implementation

**Reference files to consult:**
- `resources/views/goals/index.blade.php` — photo-polaroid pattern (lines 52-61), book cover pattern (lines 74-82)
- `resources/css/app.css` — section-label, section-label-hero, photo-polaroid, btn-pill classes
- `docs/superpowers/specs/2026-03-14-about-page-design.md` — full copy per block

**Copy source:** All Dutch copy is defined in the UX briefing (Deel 2 of the spec). Use it verbatim.

- [ ] **Step 1: Implement the full page**

Replace `resources/views/about.blade.php` with the full 6-block implementation:

```blade
<x-layout title="Over ons" description="Hartverwarmers is een gratis platform van en voor activiteitenbegeleiders in de ouderenzorg. Ontdek het verhaal, de community en het DIAMANT-model." :full-width="true">

    {{-- Block 1 — Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label section-label-hero">Over Hartverwarmers</span>
            <h1 class="mt-1">Jij hoort niet achter een computer te zitten.<br>Jij hoort bij je bewoners te zijn.</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4" style="font-weight: var(--font-weight-light);">
                Hartverwarmers bestaat zodat jij je tijd kunt steken in wat écht telt — de mensen in jouw zorg. Niet in het uitdenken en uitwerken van activiteiten. Dat doen we samen: een community van activiteitenbegeleiders die hun beste ideeën deelt, zodat jij het warm water niet telkens opnieuw hoeft uit te vinden.
            </p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 2 — Community --}}
    <section>
        <div class="max-w-5xl mx-auto px-6 py-16">
            <span class="section-label">De hartverwarmers</span>
            <h2 class="mt-1 mb-4">Samen maken we het verschil</h2>
            <p class="text-[var(--color-text-secondary)] max-w-3xl" style="font-weight: var(--font-weight-light);">
                Hartverwarmers is niet het werk van één organisatie. Het is het werk van honderden activiteitenbegeleiders, animatoren en ergotherapeuten uit heel Vlaanderen en Nederland — mensen die elke dag harten verwarmen en hun beste ideeën delen met de rest van de sector.
            </p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-10">
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">{{ number_format($aboutStats['fiches_count']) }}</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">praktijkfiches</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">{{ number_format($aboutStats['contributors_count']) }}</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">hartverwarmers</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">{{ number_format($aboutStats['users_count']) }}+</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">gebruikers</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">Gratis</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">toegankelijk</p>
                </div>
            </div>

            <a href="{{ route('contributors.index') }}" class="cta-link mt-8 inline-block">Ontdek wie er bijdraagt</a>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 3 — Foundation --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">Het fundament</span>
            <h2 class="mt-1 mb-4">Gebouwd op serieus vakmanschap</h2>
            <p class="text-[var(--color-text-secondary)] max-w-3xl" style="font-weight: var(--font-weight-light);">
                Alle activiteiten op Hartverwarmers zijn getoetst aan het <strong>DIAMANT-model</strong> — zeven doelstellingen die samen beschrijven wat een deugddoende activiteit kenmerkt. Niet als afvinklijst, maar als kompas. Het model is ontwikkeld door twee onderzoekers die de ouderenzorg door en door kennen.
            </p>

            <div class="flex gap-5 mt-8 justify-center">
                <figure class="photo-polaroid" style="transform: rotate(-3deg)">
                    <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-36 aspect-square object-cover">
                    <figcaption><strong class="text-[var(--color-text-primary)]">Maite Mallentjer</strong><br><small>Pedagoog dagbesteding, AP Hogeschool Antwerpen</small></figcaption>
                </figure>
                <figure class="photo-polaroid -mt-2" style="transform: rotate(2.5deg)">
                    <img src="/img/wonen-en-leven/nadinepraet.jpg" alt="Nadine Praet" class="w-36 aspect-square object-cover">
                    <figcaption><strong class="text-[var(--color-text-primary)]">Nadine Praet</strong><br><small>Onderzoeker ouderenzorg, Arteveldehogeschool Gent</small></figcaption>
                </figure>
            </div>

            <div class="text-center mt-6">
                <a href="{{ route('goals.index') }}" class="cta-link">Ontdek het DIAMANT-model</a>
            </div>

            {{-- Book --}}
            <div class="flex gap-6 items-start mt-12 pt-8 border-t border-[var(--color-border-light)]">
                <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="shrink-0">
                    <img src="/img/covers/hartverwarmers.jpg" alt="Hartverwarmers boekcover" class="w-28 shadow-md" style="transform: rotate(-2deg);">
                </a>
                <div>
                    <p class="text-lg font-semibold">Hartverwarmers — Deugddoende activiteiten voor woonzorgcentra</p>
                    <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Politeia, 2020</p>
                    <p class="text-[var(--color-text-secondary)] mt-2">Het boek bundelt een selectie van de beste activiteiten en legt het fundament van het DIAMANT-model uit.</p>
                    <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="cta-link mt-2 inline-block">Bekijk bij Standaard Boekhandel</a>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 4 — Story --}}
    <section>
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">Het verhaal</span>
            <h2 class="mt-1 mb-4">Geboren in één week, tijdens de eerste lockdown</h2>
            <div class="text-[var(--color-text-secondary)] space-y-4 max-w-3xl" style="font-weight: var(--font-weight-light);">
                <p>Maart 2020. Woonzorgcentra waren plots volledig afgesloten. Op sociale media deelden medewerkers creatieve manieren om bewoners — ondanks alles — een mooie dag te geven. Raamoptredens door muzikanten. Hobbykarren die langs de kamers trokken. Bingo vanuit de deuropening. Die energie mocht niet verloren gaan.</p>
                <p>In één week bouwden we Hartverwarmers: een plek om die initiatieven te bundelen, zodat elk woonzorgcentrum kon leren van wat elders werkte. Wat begon als een crisisinitiatief, is vijf jaar later nog steeds springlevend. Elke maand vinden zo'n 50 nieuwe activiteitenbegeleiders de weg naar het platform. Elke maand worden nieuwe activiteiten toegevoegd — soms zonder dat ik er iets voor doe.</p>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 5 — Personal commitment --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">Frederik Vincx — oprichter</span>
            <h2 class="mt-1 mb-4">Ik had de stekker kunnen uittrekken.<br>Dat heb ik niet gedaan.</h2>
            <div class="text-[var(--color-text-secondary)] space-y-4 max-w-3xl" style="font-weight: var(--font-weight-light);">
                <p>Hartverwarmers groeide uit een groter project — een softwarebedrijf voor woonzorgcentra dat we in 2022 stopzetten. Hartverwarmers had hetzelfde lot kunnen ondergaan. Maar de community bleef groeien, maand na maand, ook zonder actief beheer. Mensen vertrouwden op dit platform. Dat voelde als een verantwoordelijkheid.</p>
                <p>Ik heb gekozen om het te blijven dragen — alleen, persoonlijk, uit eigen zak. Ik betaal maandelijks voor de domeinnaam die dit adres levend houdt, de webserver die de site 24/7 online houdt, de e-maildienst waarmee duizenden begeleiders updates ontvangen, en de technische infrastructuur die alles draaiende houdt. De uren die ik erin steek? Die heb ik altijd gratis gegeven.</p>
            </div>

            {{-- Lancering photos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
                <img src="/img/about/lancering-activiteit.jpg" alt="Uitvoering van een activiteit — virtueel museumbezoek bij WZC Nottebohm" class="rounded-xl shadow-lg w-full">
                <img src="/img/about/lancering-boek.jpg" alt="Boekvoorstelling van het Hartverwarmers boek" class="rounded-xl shadow-lg w-full">
            </div>

            {{-- YouTube videos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <div class="aspect-video rounded-xl overflow-hidden shadow-lg">
                    <iframe src="https://www.youtube-nocookie.com/embed/k8zetWJ-Pro" title="Hartverwarmers — het ontstaan" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
                <div class="aspect-video rounded-xl overflow-hidden shadow-lg">
                    <iframe src="https://www.youtube-nocookie.com/embed/TeNR4O0TJRc" title="Hartverwarmers — de groei" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 6 — Call to action --}}
    <section>
        <div class="max-w-4xl mx-auto px-6 py-16 space-y-16">

            {{-- Primary CTA — Steun --}}
            <div class="bg-[var(--color-bg-cream)] rounded-2xl p-8 md:p-12" x-data="{ open: false }">
                <span class="section-label">Steun Hartverwarmers</span>
                <h2 class="mt-1 mb-4">Help dit platform gratis houden</h2>
                <p class="text-[var(--color-text-secondary)] max-w-2xl" style="font-weight: var(--font-weight-light);">
                    Hartverwarmers is en blijft gratis. Maar gratis bestaat niet zonder iemand die de kosten draagt. Als dit platform ooit waarde heeft gehad voor jou — als je er een activiteit op vond die een bewoner een mooie dag bezorgde — overweeg dan een bijdrage. Elk bedrag helpt.
                </p>
                <div class="mt-6">
                    <flux:button variant="primary" @click="open = !open" x-text="open ? 'Sluiten' : 'Steun Hartverwarmers'" />
                </div>
                <div x-show="open" x-collapse x-cloak class="mt-6">
                    <livewire:support-contact-form />
                </div>
            </div>

            {{-- Secondary CTA — Bijdragen --}}
            <div>
                <h3>Deel jouw activiteit</h3>
                <p class="text-[var(--color-text-secondary)] mt-2 max-w-2xl" style="font-weight: var(--font-weight-light);">
                    Heb jij een activiteit die werkt? Voeg ze toe aan de databank. Zo word jij ook een hartverwarmer — en help jij een collega die je misschien nooit zal ontmoeten.
                </p>
                <a href="{{ route('fiches.create') }}" class="cta-link mt-3 inline-block">Nieuwe fiche toevoegen</a>
            </div>

            {{-- Tertiary CTA — Delen --}}
            <div x-data="{ copied: false, async share() { const data = { title: 'Hartverwarmers', url: window.location.origin }; try { if (navigator.share) { await navigator.share(data); } else { await navigator.clipboard.writeText(data.url); this.copied = true; setTimeout(() => this.copied = false, 2000); } } catch (e) {} } }">
                <h3>Verspreid het woord</h3>
                <p class="text-[var(--color-text-secondary)] mt-2 max-w-2xl" style="font-weight: var(--font-weight-light);">
                    Ken jij een collega, een animator, een ergotherapeut die dit platform zou gebruiken? Stuur hen deze pagina. Hoe meer hartverwarmers, hoe rijker de community.
                </p>
                <button @click="share()" class="btn-pill mt-4">
                    <span x-show="!copied">Deel Hartverwarmers</span>
                    <span x-show="copied" x-cloak>Link gekopieerd!</span>
                </button>
            </div>

        </div>
    </section>
</x-layout>
```

- [ ] **Step 2: Update the existing test to match new content**

Update the `test_about_page_returns_ok` test in `tests/Feature/AboutPageTest.php` — change the `assertSeeText` to match the new headline:

```php
public function test_about_page_returns_ok(): void
{
    $response = $this->get('/over-ons');

    $response->assertOk();
    $response->assertSeeText('Jij hoort niet achter een computer te zitten.');
    $response->assertSee('Deel Hartverwarmers');
}
```

- [ ] **Step 3: Run all about page tests**

```bash
php artisan test --compact tests/Feature/AboutPageTest.php
```

Expected: All tests PASS

- [ ] **Step 4: Build frontend assets**

```bash
npm run build
```

- [ ] **Step 5: Take a screenshot to verify layout**

```bash
node scripts/screenshot.cjs /over-ons /tmp/about-desktop.png
```

Visually verify the page renders correctly with all 6 blocks.

- [ ] **Step 6: Take mobile screenshot**

```bash
node scripts/screenshot.cjs /over-ons /tmp/about-mobile.png --mobile
```

Verify mobile layout: stats grid stacks to 2×2, photos stack vertically.

- [ ] **Step 7: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add resources/views/about.blade.php tests/Feature/AboutPageTest.php
git commit -m "feat: implement about page with 6 narrative blocks"
```

---

### Task 7: Final verification

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass, no regressions.

- [ ] **Step 2: Visual QA — verify screenshots**

Read and visually verify:
- `/tmp/about-desktop.png` — all 6 blocks visible, stats grid is 4-column, photos render, YouTube embeds present, contact form hidden
- `/tmp/about-mobile.png` — responsive layout, stats 2×2, photos stack

If visual issues found, fix in `about.blade.php` and re-screenshot.

- [ ] **Step 3: Test the contact form interactively**

```bash
node scripts/screenshot.cjs /over-ons /tmp/about-form.png
```

Navigate to the page, click "Steun Hartverwarmers" button, verify the form appears with x-collapse animation.
