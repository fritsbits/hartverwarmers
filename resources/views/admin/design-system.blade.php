<x-sidebar-layout title="Design Systeem" section-label="Patroonbibliotheek" description="Alle visuele bouwstenen van Hartverwarmers — levend, altijd actueel.">

    {{-- Main content: TOC sidebar + demos --}}
    <div class="flex gap-12">

                {{-- Sticky TOC sidebar (desktop only) --}}
                <nav class="hidden lg:block w-56 shrink-0" aria-label="Inhoudsopgave">
                    <div class="sticky top-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-text-secondary)] mb-4">Inhoud</p>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#kleuren" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Kleuren & Iconen</a></li>
                            <li><a href="#typografie" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Typografie</a></li>
                            <li><a href="#knoppen" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Knoppen & Links</a></li>
                            <li><a href="#badges" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Badges & Tags</a></li>
                            <li><a href="#kaarten" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Kaarten</a></li>
                            <li><a href="#layout" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Layout Patronen</a></li>
                            <li><a href="#formulieren" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Formulieren</a></li>
                            <li><a href="#interactief" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Interactief</a></li>
                            <li><a href="#hulpmiddelen" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">Hulpmiddelen</a></li>
                        </ul>
                    </div>
                </nav>

                {{-- Component demos --}}
                <div class="flex-1 min-w-0 space-y-20">

                    {{-- ============================================================
                         1. KLEUREN
                         ============================================================ --}}
                    <div id="kleuren">
                        <span class="section-label">Referentie</span>
                        <h2 class="mt-1 mb-8">Kleuren</h2>

                        <h3 class="mb-4">Primair & Secundair</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                            <x-design-system.swatch color="var(--color-primary)" name="Primary" hex="#E8764B" />
                            <x-design-system.swatch color="var(--color-primary-hover)" name="Primary Hover" hex="#D4683F" />
                            <x-design-system.swatch color="var(--color-secondary)" name="Secondary" hex="#4CB7C5" />
                            <x-design-system.swatch color="var(--color-yellow)" name="Yellow" hex="#F4C44E" />
                        </div>

                        <h3 class="mb-4">Accenten</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                            <x-design-system.swatch color="var(--color-accent-blue)" name="Accent Blue" hex="#4CB7C5" />
                            <x-design-system.swatch color="var(--color-accent-yellow)" name="Accent Yellow" hex="#F4C44E" />
                            <x-design-system.swatch color="var(--color-accent-purple)" name="Accent Purple" hex="#B57BB3" />
                        </div>

                        <h3 class="mb-4">Achtergronden</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                            <x-design-system.swatch color="var(--color-bg-white)" name="BG White" hex="#FFFFFF" border />
                            <x-design-system.swatch color="var(--color-bg-cream)" name="BG Cream" hex="#FEF8F4" />
                            <x-design-system.swatch color="var(--color-bg-subtle)" name="BG Subtle" hex="#F5F0EC" />
                            <x-design-system.swatch color="var(--color-bg-accent-light)" name="BG Accent Light" hex="#FDF3EE" />
                        </div>

                        <h3 class="mb-4">Tekst & Randen</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <x-design-system.swatch color="var(--color-text-primary)" name="Text Primary" hex="#231E1A" />
                            <x-design-system.swatch color="var(--color-text-secondary)" name="Text Secondary" hex="#756C65" />
                            <x-design-system.swatch color="var(--color-text-tertiary)" name="Text Tertiary" hex="#C0B5AE" />
                            <x-design-system.swatch color="var(--color-border-hover)" name="Border Hover" hex="#DDD5CD" />
                            <x-design-system.swatch color="var(--color-border-light)" name="Border Light" hex="#EBE4DE" />
                        </div>

                        <h3 class="mb-4 mt-8">Iconen</h3>
                        <p class="text-meta mb-4">Meta-iconen (kudos, reacties, statistieken) gebruiken <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">--color-text-tertiary</code> (#C0B5AE) — lichter dan secondary tekst, zwaarder dan randen. Dit houdt het chrome zacht zonder te verdwijnen.</p>
                        <div class="p-6 rounded-xl bg-[var(--color-bg-cream)] mb-4">
                            <div class="flex items-center gap-4 text-sm text-[var(--color-text-tertiary)]">
                                <span class="flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                        <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                                    </svg>
                                    13
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                        <path d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                                    </svg>
                                    4
                                </span>
                            </div>
                        </div>
                        <p class="text-meta mb-2"><strong>Regels:</strong></p>
                        <ul class="text-meta space-y-1 mb-4 list-disc list-inside">
                            <li>Grootte: <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">w-4 h-4</code> (16px) voor inline meta</li>
                            <li>Kleur: altijd <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">text-[var(--color-text-tertiary)]</code> op de container</li>
                            <li>Gap: <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">gap-1.5</code> tussen icoon en getal, <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">gap-4</code> tussen paren</li>
                            <li>Gebruik <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">fill="currentColor"</code> — nooit hardcoded vulkleur</li>
                        </ul>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="flex items-center gap-4 text-sm text-[var(--color-text-tertiary)]"&gt;
    &lt;span class="flex items-center gap-1.5"&gt;
        &lt;svg ... class="w-4 h-4"&gt;...&lt;/svg&gt;
        13
    &lt;/span&gt;
    &lt;span class="flex items-center gap-1.5"&gt;
        &lt;svg ... class="w-4 h-4"&gt;...&lt;/svg&gt;
        4
    &lt;/span&gt;
&lt;/div&gt;</code></pre>
                        </details>
                    </div>

                    {{-- ============================================================
                         2. TYPOGRAFIE
                         ============================================================ --}}
                    <div id="typografie">
                        <span class="section-label">Referentie</span>
                        <h2 class="mt-1 mb-8">Typografie</h2>

                        <h3 class="mb-4">Koppen</h3>
                        <p class="text-meta mb-4">Headings gebruiken <strong>Aleo</strong> (slab-serif, bold 700) via <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">font-heading</code>. H1&ndash;H3 worden automatisch gestyled via de base layer.</p>
                        <div class="space-y-4 mb-8 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <h1>H1 — Muziekbingo voor bewoners</h1>
                            <h2>H2 — Praktische uitwerkingen</h2>
                            <h3>H3 — Materialen en tips</h3>
                            <h4>H4 — Voorbereiding</h4>
                        </div>

                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;h1&gt;H1 &mdash; Muziekbingo voor bewoners&lt;/h1&gt;
&lt;h2&gt;H2 &mdash; Praktische uitwerkingen&lt;/h2&gt;
&lt;h3&gt;H3 &mdash; Materialen en tips&lt;/h3&gt;
&lt;h4&gt;H4 &mdash; Voorbereiding&lt;/h4&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Section Label + H2 patroon</h3>
                        <p class="text-meta mb-4">Oranje uppercase eyebrow boven een outcome-driven H2. Gebruik dit voor elke sectie-header.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <span class="section-label">Ontdek</span>
                            <h2 class="mt-1">Initiatieven die inspireren</h2>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;span class="section-label"&gt;Ontdek&lt;/span&gt;
&lt;h2 class="mt-1"&gt;Initiatieven die inspireren&lt;/h2&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Card title patroon</h3>
                        <p class="text-meta mb-4">Flux heading in cards heeft expliciet <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">font-heading font-bold</code> nodig.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <flux:heading size="lg" class="font-heading font-bold">Muziekbingo met jaren 60 hits</flux:heading>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:heading size="lg" class="font-heading font-bold"&gt;Titel&lt;/flux:heading&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Tekst hulpklassen</h3>
                        <div class="space-y-3 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <p class="text-meta">Dit is <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">.text-meta</code> — lichtgewicht, secundaire kleur</p>
                            <p class="text-lg" style="font-weight: var(--font-weight-light); color: var(--color-text-secondary);">Dit is de intro-stijl — groot, licht, secundair</p>
                        </div>
                    </div>

                    {{-- ============================================================
                         3. KNOPPEN & LINKS
                         ============================================================ --}}
                    <div id="knoppen">
                        <span class="section-label">Interactie</span>
                        <h2 class="mt-1 mb-8">Knoppen & Links</h2>

                        <h3 class="mb-4">Flux Buttons</h3>
                        <p class="text-meta mb-4">Gebruik <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;flux:button&gt;</code> voor formulieracties. Variant <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">primary</code> voor hoofdactie, <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">ghost</code> voor secundair.</p>
                        <div class="flex flex-wrap items-center gap-6 mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <div class="text-center">
                                <flux:button variant="primary">Opslaan</flux:button>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Primary</p>
                            </div>
                            <div class="text-center">
                                <flux:button variant="filled">Filled</flux:button>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Filled</p>
                            </div>
                            <div class="text-center">
                                <flux:button variant="ghost">Annuleren</flux:button>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Ghost</p>
                            </div>
                            <div class="text-center">
                                <flux:button variant="danger">Verwijderen</flux:button>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Danger</p>
                            </div>
                            <div class="text-center">
                                <flux:button variant="primary" size="sm">Klein</flux:button>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Primary sm</p>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:button variant="primary"&gt;Opslaan&lt;/flux:button&gt;
&lt;flux:button variant="filled"&gt;Filled&lt;/flux:button&gt;
&lt;flux:button variant="ghost"&gt;Annuleren&lt;/flux:button&gt;
&lt;flux:button variant="danger"&gt;Verwijderen&lt;/flux:button&gt;
&lt;flux:button variant="primary" size="sm"&gt;Klein&lt;/flux:button&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">CTA Link</h3>
                        <p class="text-meta mb-4">De standaard "meer zien"-stijl. Tekst + geanimeerde pijl. Gebruik voor navigatielinks, niet voor acties.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <span class="cta-link">Bekijk alle initiatieven</span>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;a href="..." class="cta-link"&gt;Bekijk alle initiatieven&lt;/a&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Pill Button <span class="text-sm font-normal text-[var(--color-text-secondary)]">(deprecated)</span></h3>
                        <p class="text-meta mb-4">Gebruik bij voorkeur <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;flux:button variant="primary"&gt;</code> in plaats van <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">.btn-pill</code>.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <span class="btn-pill">Deel jouw uitwerking</span>
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;a href="..." class="btn-pill"&gt;Deel jouw uitwerking&lt;/a&gt;</code></pre>
                        </details>
                    </div>

                    {{-- ============================================================
                         4. BADGES & TAGS
                         ============================================================ --}}
                    <div id="badges">
                        <span class="section-label">Elementen</span>
                        <h2 class="mt-1 mb-8">Badges & Tags</h2>

                        <h3 class="mb-4">DIAMANT gem</h3>
                        <p class="text-meta mb-4">Faceted gem-shaped SVG met DIAMANT-letter. Gebruik <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;x-diamant-gem&gt;</code>. Vijf maten, actief/inactief.</p>
                        <div class="flex flex-wrap items-end gap-6 mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            @foreach(['lg', 'md', 'sm', 'xs', 'xxs'] as $gemSize)
                                <div class="text-center">
                                    <x-diamant-gem letter="D" :size="$gemSize" />
                                    <p class="text-xs text-[var(--color-text-secondary)] mt-1">{{ $gemSize }}</p>
                                </div>
                            @endforeach
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-diamant-gem letter="D" size="lg" /&gt;
&lt;x-diamant-gem letter="D" size="md" /&gt;
&lt;x-diamant-gem letter="D" size="sm" /&gt;
&lt;x-diamant-gem letter="D" size="xs" /&gt;
&lt;x-diamant-gem letter="D" size="xxs" /&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Actief vs. inactief</h3>
                        <p class="text-meta mb-4">Inactieve gems hebben een subtiele achtergrond en gedempte tekst.</p>
                        <div class="flex flex-wrap items-center gap-4 mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <div class="text-center">
                                <x-diamant-gem letter="D" size="md" :active="true" />
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Actief</p>
                            </div>
                            <div class="text-center">
                                <x-diamant-gem letter="D" size="md" :active="false" />
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Inactief</p>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-diamant-gem letter="D" size="md" :active="true" /&gt;
&lt;x-diamant-gem letter="D" size="md" :active="false" /&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">DIAMANT profiel</h3>
                        <p class="text-meta mb-4">Toont alle 7 doelen als rij van actief/inactief gems. Gebruik <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;x-diamant-profile&gt;</code>.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <x-diamant-profile :goalTags="$goalTags->take(3)" />
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-diamant-profile :goalTags="$goalTags" /&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">DIAMANT pills</h3>
                        <p class="text-meta mb-4">Compacte doellinks met gem-icoon. Actief en inactief.</p>
                        <div class="flex flex-wrap items-end gap-2 mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            @foreach(array_slice(array_values($facets), 0, 3) as $facet)
                                <span class="diamant-pill">
                                    <x-diamant-gem :letter="$facet['letter']" size="xxs" />
                                    {{ $facet['keyword'] }}
                                </span>
                            @endforeach
                            <div class="text-center">
                                <span class="diamant-pill diamant-pill-inactive">
                                    <x-diamant-gem letter="N" size="xxs" :active="false" />
                                    Normalisatie
                                </span>
                                <p class="text-xs text-[var(--color-text-secondary)] mt-1">Inactief</p>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;span class="diamant-pill"&gt;
    &lt;x-diamant-gem letter="D" size="xxs" /&gt;
    Doen
&lt;/span&gt;

&lt;!-- Inactief --&gt;
&lt;span class="diamant-pill diamant-pill-inactive"&gt;
    &lt;x-diamant-gem letter="N" size="xxs" :active="false" /&gt;
    Normalisatie
&lt;/span&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Diamond indicator</h3>
                        <p class="text-meta mb-4">Licht oranje pill met gem-icoon — toont op fiche-kaarten en titels.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <x-diamond-badge />
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-diamond-badge /&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Flux badges</h3>
                        <p class="text-meta mb-4">Gebruik voor tags en metadata labels.</p>
                        <div class="flex flex-wrap items-center gap-2 mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <flux:badge size="sm" color="zinc">Muziek</flux:badge>
                            <flux:badge size="sm" color="zinc">Bewegen</flux:badge>
                            <flux:badge size="sm" color="zinc">Natuur</flux:badge>
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:badge size="sm" color="zinc"&gt;Muziek&lt;/flux:badge&gt;</code></pre>
                        </details>
                    </div>

                    {{-- ============================================================
                         5. KAARTEN
                         ============================================================ --}}
                    <div id="kaarten">
                        <span class="section-label">Componenten</span>
                        <h2 class="mt-1 mb-8">Kaarten</h2>

                        <h3 class="mb-4">Initiative Card</h3>
                        <p class="text-meta mb-4">Klikbare kaart voor initiatieven. Gebruikt <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;x-initiative-card&gt;</code>.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                            <div class="text-center">
                                <x-initiative-card :initiative="$initiative" />
                                <p class="text-xs text-[var(--color-text-secondary)] mt-2">Standaard</p>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-initiative-card :initiative="$initiative" /&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Fiche Card</h3>
                        <p class="text-meta mb-4">Klikbare kaart voor fiches. Toont auteur, optioneel tags en diamond badge.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                            <div class="text-center">
                                <x-fiche-card :fiche="$fiche" />
                                <p class="text-xs text-[var(--color-text-secondary)] mt-2">Standaard</p>
                            </div>
                            <div class="text-center">
                                <x-fiche-card :fiche="$fiche" />
                                <p class="text-xs text-[var(--color-text-secondary)] mt-2">Met diamond (auto)</p>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-fiche-card :fiche="$fiche" /&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Fiche List (compact)</h3>
                        <p class="text-meta mb-4">Card-stijl rijen voor compacte fiche-lijst op initiatief-detailpagina's. Elk item toont een <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;x-fiche-icon&gt;</code> — een gekleurd schijfje met een contextueel Lucide-icoon, automatisch toegewezen via AI. Kleur wordt bepaald door <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">fiche->id % 6</code>. Klassen: <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">.fiche-list-item</code>, <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">.fiche-list-icon</code>, <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">.fiche-list-kudos</code>.</p>

                        {{-- Fiche icon component demo: sizes --}}
                        <h4 class="text-sm font-semibold text-[var(--color-text-secondary)] mb-3">Fiche Icon — formaten</h4>
                        <div class="flex items-center gap-6 mb-6">
                            @php
                                $demoFiche = App\Models\Fiche::whereNotNull('icon')->first() ?? (object)['id' => 1, 'icon' => 'music'];
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                <x-fiche-icon :fiche="$demoFiche" size="sm" />
                                <span class="text-xs text-[var(--color-text-secondary)]">sm (32px)</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <x-fiche-icon :fiche="$demoFiche" size="md" />
                                <span class="text-xs text-[var(--color-text-secondary)]">md (48px)</span>
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <x-fiche-icon :fiche="$demoFiche" size="lg" />
                                <span class="text-xs text-[var(--color-text-secondary)]">lg (64px)</span>
                            </div>
                        </div>

                        {{-- Color palette demo --}}
                        <h4 class="text-sm font-semibold text-[var(--color-text-secondary)] mb-3">Kleurenpalet (6 kleuren, ID-gebaseerd)</h4>
                        <div class="flex items-center gap-3 mb-8">
                            @foreach(config('fiche-icons.colors') as $i => $color)
                                <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0" style="background-color: {{ $color['bg'] }}; color: {{ $color['text'] }}">
                                    <x-lucide-heart class="w-6 h-6" />
                                </div>
                            @endforeach
                        </div>

                        {{-- Live fiche list demo --}}
                        <h4 class="text-sm font-semibold text-[var(--color-text-secondary)] mb-3">Lijst met echte fiches</h4>
                        <div class="max-w-xl mb-4 space-y-2">
                            @foreach(App\Models\Fiche::whereNotNull('icon')->with('user')->inRandomOrder()->take(4)->get() as $demoFiche)
                                <a href="#" class="fiche-list-item" onclick="event.preventDefault()">
                                    <x-fiche-icon :fiche="$demoFiche" class="fiche-list-icon" />
                                    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                        <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] truncate">{{ $demoFiche->title }}</span>
                                        <span class="text-xs text-[var(--color-text-secondary)]">{{ $demoFiche->user?->full_name }}@if($demoFiche->user?->organisation), {{ $demoFiche->user->organisation }}@endif</span>
                                    </div>
                                    <span class="fiche-list-kudos {{ $demoFiche->kudos_count > 0 ? 'fiche-list-kudos-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/></svg>
                                        {{ $demoFiche->kudos_count }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                        <p class="text-xs text-[var(--color-text-secondary)] mb-2">Hover over de rijen voor een subtiel lift-schaduw effect. Iconen worden automatisch toegewezen via AI bij het aanmaken van een fiche.</p>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="space-y-2"&gt;
    &lt;a href="..." class="fiche-list-item"&gt;
        &lt;x-fiche-icon :fiche="$fiche" class="fiche-list-icon" /&gt;
        &lt;div class="flex flex-col gap-0.5 min-w-0 flex-1"&gt;
            &lt;span class="font-body font-semibold text-lg ..."&gt;Titel&lt;/span&gt;
            &lt;span class="text-xs ..."&gt;Auteur, Organisatie&lt;/span&gt;
        &lt;/div&gt;
        &lt;span class="fiche-list-kudos fiche-list-kudos-active"&gt;
            &lt;svg ...&gt;&lt;/svg&gt; 12
        &lt;/span&gt;
    &lt;/a&gt;
&lt;/div&gt;
&lt;button class="fiche-list-expand"&gt;+ 5 meer&lt;/button&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Content Card (CSS)</h3>
                        <p class="text-meta mb-4">Pure CSS kaart met hover-lift. Gebruik voor klikbare items die geen Flux card nodig hebben.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                            <div class="content-card">
                                <div class="card-content">
                                    <div class="card-title">Voorbeeld content card</div>
                                    <div class="card-description">Met hover effect, schaduw en border animatie.</div>
                                </div>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="content-card"&gt;
    &lt;div class="card-content"&gt;
        &lt;div class="card-title"&gt;Titel&lt;/div&gt;
        &lt;div class="card-description"&gt;Beschrijving&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Flux Card (statisch)</h3>
                        <p class="text-meta mb-4">Gebruik <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;flux:card&gt;</code> voor statische containers (detail pagina's, sidebars).</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                            <flux:card>
                                <flux:heading size="lg" class="font-heading font-bold">Flux Card</flux:heading>
                                <flux:text class="mt-2">Statische container voor informatie, geen hover effect.</flux:text>
                            </flux:card>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:card&gt;
    &lt;flux:heading size="lg" class="font-heading font-bold"&gt;Titel&lt;/flux:heading&gt;
    &lt;flux:text class="mt-2"&gt;Inhoud&lt;/flux:text&gt;
&lt;/flux:card&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Quote Paper</h3>
                        <p class="text-meta mb-4">Gelinieerd-papier stijl voor citaten op doelpagina's. Licht geroteerd met handschrift-font.</p>
                        <div class="max-w-sm mb-4">
                            <div class="relative">
                                <div class="quote-paper-mark">&ldquo;</div>
                                <div class="quote-paper">
                                    <p class="quote-ik-wil">Ik wil graag...</p>
                                    <p>samen muziek maken en dansen zoals vroeger.</p>
                                </div>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="relative"&gt;
    &lt;div class="quote-paper-mark"&gt;&amp;ldquo;&lt;/div&gt;
    &lt;div class="quote-paper"&gt;
        &lt;p class="quote-ik-wil"&gt;Ik wil graag...&lt;/p&gt;
        &lt;p&gt;samen muziek maken.&lt;/p&gt;
    &lt;/div&gt;
&lt;/div&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Practice Example</h3>
                        <p class="text-meta mb-4">Horizontaal avatar + tekst patroon voor praktijkvoorbeelden op doelpagina's.</p>
                        <div class="max-w-lg mb-4">
                            <div class="practice-example">
                                <div class="practice-example-avatar">
                                    <div class="w-full h-full rounded-full bg-[var(--color-bg-subtle)] flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-heading font-bold mb-1">Huisvrouw Rosa</h3>
                                    <p class="text-[var(--color-text-secondary)]">Helpt elke ochtend met het vouwen van servetten. Ze doet het graag.</p>
                                </div>
                            </div>
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="practice-example"&gt;
    &lt;div class="practice-example-avatar"&gt;...&lt;/div&gt;
    &lt;div class="flex-1 min-w-0"&gt;
        &lt;h3 class="text-lg font-heading font-bold mb-1"&gt;Rol Naam&lt;/h3&gt;
        &lt;p&gt;Beschrijving&lt;/p&gt;
    &lt;/div&gt;
&lt;/div&gt;</code></pre>
                        </details>
                    </div>

                    {{-- ============================================================
                         6. LAYOUT PATRONEN
                         ============================================================ --}}
                    <div id="layout">
                        <span class="section-label">Structuur</span>
                        <h2 class="mt-1 mb-8">Layout Patronen</h2>

                        <h3 class="mb-4">Sectie-structuur</h3>
                        <p class="text-meta mb-4">Elke pagina volgt: cream hero, HR separator, witte content secties. Gebruik <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;x-layout :full-width="true"&gt;</code>.</p>
                        <div class="mb-4 rounded-xl overflow-hidden border border-[var(--color-border-light)]">
                            <div class="bg-[var(--color-bg-cream)] p-4 text-center text-sm">
                                <strong>Hero sectie</strong> — bg-cream, breadcrumbs, section-label, h1
                            </div>
                            <hr class="border-[var(--color-border-light)]">
                            <div class="bg-white p-4 text-center text-sm">
                                <strong>Content sectie</strong> — max-w-6xl mx-auto px-6 py-16
                            </div>
                            <hr class="border-[var(--color-border-light)]">
                            <div class="bg-white p-4 text-center text-sm">
                                <strong>Content sectie</strong> — herhaal patroon
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-layout :full-width="true"&gt;
    &lt;section class="bg-[var(--color-bg-cream)]"&gt;
        &lt;div class="max-w-6xl mx-auto px-6 pt-8 pb-12"&gt;
            &lt;!-- Hero content --&gt;
        &lt;/div&gt;
    &lt;/section&gt;
    &lt;hr class="border-[var(--color-border-light)]"&gt;
    &lt;section&gt;
        &lt;div class="max-w-6xl mx-auto px-6 py-16"&gt;
            &lt;!-- Content --&gt;
        &lt;/div&gt;
    &lt;/section&gt;
&lt;/x-layout&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Intro block</h3>
                        <p class="text-meta mb-4">Gecentreerd tekstblok (max 800px) voor pagina-introductie.</p>
                        <div class="mb-4 rounded-xl overflow-hidden border border-[var(--color-border-light)] bg-[var(--color-bg-cream)]">
                            <div class="intro-block">
                                <h1>Ontdek initiatieven</h1>
                                <p>Laat je inspireren door de praktijkuitwerkingen van collega-activiteitenbegeleiders.</p>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="intro-block"&gt;
    &lt;h1&gt;Ontdek initiatieven&lt;/h1&gt;
    &lt;p&gt;Beschrijving...&lt;/p&gt;
&lt;/div&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Meta group</h3>
                        <p class="text-meta mb-4">Icoon + tekst metadata rijen voor tellingen, auteur, datum etc.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <div class="meta-group">
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0"/></svg>
                                    Maria Janssen
                                </span>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                                    15 februari 2026
                                </span>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                    3 fiches
                                </span>
                            </div>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="meta-group"&gt;
    &lt;span class="meta-item"&gt;
        &lt;svg ...&gt;&lt;/svg&gt;
        Maria Janssen
    &lt;/span&gt;
    &lt;span class="meta-item"&gt;...&lt;/span&gt;
&lt;/div&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Reflection question</h3>
                        <p class="text-meta mb-4">Reflectievragen met diamond badge. Lichtgewicht tekst zonder achtergrondblokken.</p>
                        <div class="space-y-6 mb-4">
                            <div class="flex items-start gap-4">
                                <span class="question-badge">&#9671;</span>
                                <p class="text-xl font-light text-[var(--color-text-secondary)] leading-relaxed">Hoe kun je bewoners meer keuzevrijheid geven bij activiteiten?</p>
                            </div>
                            <div class="flex items-start gap-4">
                                <span class="question-badge">&#9671;</span>
                                <p class="text-xl font-light text-[var(--color-text-secondary)] leading-relaxed">Welke kleine aanpassingen maken een groot verschil?</p>
                            </div>
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="flex items-start gap-4"&gt;
    &lt;span class="question-badge"&gt;&amp;#9671;&lt;/span&gt;
    &lt;p class="text-xl font-light ..."&gt;Vraag hier...&lt;/p&gt;
&lt;/div&gt;</code></pre>
                        </details>
                    </div>

                    {{-- ============================================================
                         7. FORMULIEREN
                         ============================================================ --}}
                    <div id="formulieren">
                        <span class="section-label">Invoer</span>
                        <h2 class="mt-1 mb-8">Formulieren</h2>

                        <h3 class="mb-4">Flux formulierelementen</h3>
                        <p class="text-meta mb-4">Alle formuliervelden gebruiken <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;flux:*&gt;</code> componenten.</p>
                        <div class="max-w-lg space-y-4 mb-4 p-6 rounded-xl bg-white border border-zinc-200">
                            <flux:field>
                                <flux:label>Naam <span class="field-tag ml-1">Verplicht</span></flux:label>
                                <flux:input placeholder="Maria Janssen" />
                            </flux:field>
                            <flux:input label="E-mail" type="email" placeholder="maria@voorbeeld.nl" />
                            <flux:textarea label="Beschrijving" placeholder="Beschrijf het initiatief..." rows="3" />
                            <flux:select label="Rol" placeholder="Kies een rol...">
                                <flux:select.option value="contributor">Bijdrager</flux:select.option>
                                <flux:select.option value="curator">Curator</flux:select.option>
                                <flux:select.option value="admin">Admin</flux:select.option>
                            </flux:select>
                            <div>
                                <flux:input label="Met foutmelding" placeholder="Te kort" />
                                <flux:error name="voorbeeld">Dit veld is verplicht.</flux:error>
                            </div>
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:field&gt;
    &lt;flux:label&gt;Naam &lt;span class="field-tag ml-1"&gt;Verplicht&lt;/span&gt;&lt;/flux:label&gt;
    &lt;flux:input placeholder="..." /&gt;
&lt;/flux:field&gt;
&lt;flux:textarea label="Beschrijving" rows="3" /&gt;
&lt;flux:select label="Rol"&gt;
    &lt;flux:select.option value="contributor"&gt;Bijdrager&lt;/flux:select.option&gt;
&lt;/flux:select&gt;
&lt;flux:error name="field"&gt;Foutmelding&lt;/flux:error&gt;</code></pre>
                        </details>
                    </div>

                    {{-- ============================================================
                         8. INTERACTIEF
                         ============================================================ --}}
                    <div id="interactief">
                        <span class="section-label">UI Patronen</span>
                        <h2 class="mt-1 mb-8">Interactief</h2>

                        <h3 class="mb-4">Breadcrumbs</h3>
                        <p class="text-meta mb-4">Gebruik <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">&lt;flux:breadcrumbs&gt;</code> bovenaan elke pagina in de hero sectie.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <flux:breadcrumbs>
                                <flux:breadcrumbs.item href="#">Home</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item href="#">Initiatieven</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item>Muziekbingo</flux:breadcrumbs.item>
                            </flux:breadcrumbs>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:breadcrumbs&gt;
    &lt;flux:breadcrumbs.item href="..."&gt;Home&lt;/flux:breadcrumbs.item&gt;
    &lt;flux:breadcrumbs.item href="..."&gt;Initiatieven&lt;/flux:breadcrumbs.item&gt;
    &lt;flux:breadcrumbs.item&gt;Muziekbingo&lt;/flux:breadcrumbs.item&gt;
&lt;/flux:breadcrumbs&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Tooltip</h3>
                        <p class="text-meta mb-4">Warme stijl override via CSS. Gebruik voor extra context bij iconen of labels.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <flux:tooltip content="Dit is een tooltip" position="right">
                                <flux:button variant="ghost" size="sm">Hover mij</flux:button>
                            </flux:tooltip>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:tooltip content="Tooltip tekst"&gt;
    &lt;flux:button variant="ghost"&gt;Hover mij&lt;/flux:button&gt;
&lt;/flux:tooltip&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Dropdown</h3>
                        <p class="text-meta mb-4">Gebruik voor contextmenu's en actielijsten.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">Opties</flux:button>
                                <flux:menu>
                                    <flux:menu.item>Bewerken</flux:menu.item>
                                    <flux:menu.item>Dupliceren</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item variant="danger">Verwijderen</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:dropdown&gt;
    &lt;flux:button variant="ghost" icon-trailing="chevron-down"&gt;Opties&lt;/flux:button&gt;
    &lt;flux:menu&gt;
        &lt;flux:menu.item&gt;Bewerken&lt;/flux:menu.item&gt;
        &lt;flux:menu.separator /&gt;
        &lt;flux:menu.item variant="danger"&gt;Verwijderen&lt;/flux:menu.item&gt;
    &lt;/flux:menu&gt;
&lt;/flux:dropdown&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">Modal</h3>
                        <p class="text-meta mb-4">Gebruik voor bevestigingsdialogen en formulieren die context nodig hebben.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <flux:modal.trigger name="design-system-modal">
                                <flux:button variant="primary">Open modal</flux:button>
                            </flux:modal.trigger>
                            <flux:modal name="design-system-modal" class="max-w-md">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg" class="font-heading font-bold">Weet je het zeker?</flux:heading>
                                        <flux:text class="mt-2">Deze actie kan niet ongedaan worden gemaakt.</flux:text>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">Annuleren</flux:button>
                                        </flux:modal.close>
                                        <flux:modal.close>
                                            <flux:button variant="danger">Verwijderen</flux:button>
                                        </flux:modal.close>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                        <details class="mb-8">
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;flux:modal.trigger name="my-modal"&gt;
    &lt;flux:button&gt;Open modal&lt;/flux:button&gt;
&lt;/flux:modal.trigger&gt;
&lt;flux:modal name="my-modal" class="max-w-md"&gt;
    &lt;flux:heading&gt;Titel&lt;/flux:heading&gt;
    &lt;flux:modal.close&gt;
        &lt;flux:button variant="ghost"&gt;Sluiten&lt;/flux:button&gt;
    &lt;/flux:modal.close&gt;
&lt;/flux:modal&gt;</code></pre>
                        </details>

                        <h3 class="mb-4">User Avatar</h3>
                        <p class="text-meta mb-4">Enkele gebruikersavatar met profielfoto of deterministische kleur + twee initialen. Kleur is gebaseerd op user ID zodat elke persoon altijd dezelfde kleur krijgt. Beschikbare maten: xs, sm, md, base, lg, xl, 2xl.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <div class="flex items-end gap-4">
                                @foreach(['xs', 'sm', 'md', 'base', 'lg', 'xl'] as $size)
                                    <div class="flex flex-col items-center gap-1">
                                        <x-user-avatar :user="$users->skip(array_search($size, ['xs', 'sm', 'md', 'base', 'lg', 'xl']))->first() ?? $users->first()" :size="$size" />
                                        <span class="text-[10px] text-[var(--color-text-secondary)]">{{ $size }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-user-avatar :user="$user" size="md" /&gt;
&lt;x-user-avatar :user="$user" size="lg" class="ring-2 ring-white" /&gt;</code></pre>
                        </details>

                        <h3 class="mb-4 mt-8">Avatar Stack</h3>
                        <p class="text-meta mb-4">Overlappende avatarcirkels met initialen. Toont +N badge bij meer gebruikers. Gebruikt <code>&lt;x-user-avatar&gt;</code> intern.</p>
                        <div class="mb-4 p-6 rounded-xl bg-[var(--color-bg-cream)]">
                            <x-avatar-stack :users="$users" :max="4" />
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;x-avatar-stack :users="$users" :max="5" /&gt;</code></pre>
                        </details>
                    </div>

                    {{-- ============================================================
                         9. HULPMIDDELEN
                         ============================================================ --}}
                    <div id="hulpmiddelen">
                        <span class="section-label">Tokens</span>
                        <h2 class="mt-1 mb-8">Hulpmiddelen</h2>

                        <h3 class="mb-4">Schaduw & Radius</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                            <div class="p-6 bg-white rounded-[var(--radius-sm)] text-center text-sm" style="box-shadow: var(--shadow-card);">
                                <code class="text-xs">--shadow-card</code><br>
                                <code class="text-xs">--radius-sm (6px)</code>
                            </div>
                            <div class="p-6 bg-white rounded-[var(--radius-md)] text-center text-sm" style="box-shadow: var(--shadow-card-hover);">
                                <code class="text-xs">--shadow-card-hover</code><br>
                                <code class="text-xs">--radius-md (12px)</code>
                            </div>
                            <div class="p-6 bg-white rounded-[var(--radius-full)] text-center text-sm" style="box-shadow: var(--shadow-card);">
                                <code class="text-xs">--shadow-card</code><br>
                                <code class="text-xs">--radius-full (9999px)</code>
                            </div>
                        </div>

                        <h3 class="mb-4">Spacing tokens</h3>
                        <div class="space-y-2 mb-8">
                            @foreach([
                                'xs' => '0.25rem (4px)',
                                'sm' => '0.5rem (8px)',
                                'md' => '1rem (16px)',
                                'lg' => '1.5rem (24px)',
                                'xl' => '2rem (32px)',
                                '2xl' => '3rem (48px)',
                                '3xl' => '4rem (64px)',
                                '4xl' => '6rem (96px)',
                            ] as $name => $value)
                                <div class="flex items-center gap-4">
                                    <code class="text-xs w-20 shrink-0">--space-{{ $name }}</code>
                                    <div class="h-3 bg-[var(--color-primary)] rounded-full" style="width: var(--space-{{ $name }});"></div>
                                    <span class="text-xs text-[var(--color-text-secondary)]">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>

                        <h3 class="mb-4">Responsive breakpoints</h3>
                        <div class="overflow-x-auto mb-8">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-[var(--color-border-light)]">
                                        <th class="text-left py-2 font-semibold">Prefix</th>
                                        <th class="text-left py-2 font-semibold">Min-width</th>
                                        <th class="text-left py-2 font-semibold">Gebruik</th>
                                    </tr>
                                </thead>
                                <tbody class="text-[var(--color-text-secondary)]">
                                    <tr class="border-b border-[var(--color-border-light)]"><td class="py-2"><code>sm:</code></td><td>640px</td><td>Grote telefoons</td></tr>
                                    <tr class="border-b border-[var(--color-border-light)]"><td class="py-2"><code>md:</code></td><td>768px</td><td>Tablets</td></tr>
                                    <tr class="border-b border-[var(--color-border-light)]"><td class="py-2"><code>lg:</code></td><td>1024px</td><td>Laptops</td></tr>
                                    <tr><td class="py-2"><code>xl:</code></td><td>1280px</td><td>Desktops</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h3 class="mb-4">Practical content prose</h3>
                        <p class="text-meta mb-4">Klasse <code class="text-sm bg-zinc-100 px-1.5 py-0.5 rounded">.practical-content</code> voor rich HTML uit materials JSON.</p>
                        <div class="max-w-lg mb-4 p-6 rounded-xl border border-[var(--color-border-light)]">
                            <div class="practical-content">
                                <h3>Voorbereiding</h3>
                                <p>Zoek van tevoren <a href="#">bekende liedjes</a> uit de jaren 50 en 60. Maak bingokaarten met songtitels.</p>
                                <h3>Benodigdheden</h3>
                                <p>Geluidsinstallatie, bingokaarten, stiften, kleine prijsjes.</p>
                            </div>
                        </div>
                        <details>
                            <summary class="text-sm font-semibold text-[var(--color-text-secondary)] cursor-pointer">Toon markup</summary>
                            <pre class="mt-2 p-4 bg-zinc-50 rounded-lg text-sm overflow-x-auto"><code>&lt;div class="practical-content"&gt;
    &lt;h3&gt;Voorbereiding&lt;/h3&gt;
    &lt;p&gt;Inhoud met &lt;a href="..."&gt;links&lt;/a&gt;...&lt;/p&gt;
&lt;/div&gt;</code></pre>
                        </details>

                        <h3 class="mt-12 mb-4">Livewire componenten (referentie)</h3>
                        <p class="text-meta mb-2">Deze componenten worden hier niet live gerenderd omdat ze Livewire state nodig hebben. Bekijk ze op de fiches.</p>
                        <ul class="list-disc list-inside text-sm text-[var(--color-text-secondary)] space-y-1">
                            <li><code>&lt;livewire:fiche-kudos&gt;</code> — Kudos-hartjes met floating animatie</li>
                            <li><code>&lt;livewire:fiche-comments&gt;</code> — Commentaarsectie met real-time updates</li>
                            <li><code>&lt;livewire:search&gt;</code> — Command palette zoekfunctie</li>
                            <li><code>&lt;x-file-preview-carousel&gt;</code> — Bestandsvoorbeelden carousel met slides</li>
                        </ul>
                    </div>

                </div>
            </div>

</x-sidebar-layout>
