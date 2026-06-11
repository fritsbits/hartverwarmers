<div>
    @if ($sent)
        <div class="text-center py-6">
            <figure class="photo-polaroid inline-block mb-5" style="transform: rotate(-2deg)">
                <img src="/img/about/frederik-vincx.webp" alt="Frederik Vincx" width="112" height="112" loading="lazy" class="w-28 aspect-square object-cover object-top">
                <figcaption><strong class="text-[var(--color-text-primary)]">Frederik</strong></figcaption>
            </figure>
            <p class="text-xl font-heading font-bold text-[var(--color-text-primary)]">Bedankt, {{ str($name)->trim()->before(' ') }}!</p>
            <p class="mt-2 text-[var(--color-text-secondary)] max-w-sm mx-auto" style="font-weight: var(--font-weight-light);">
                Je bericht is bij mij terecht. Ik lees alles zelf en antwoord meestal binnen een paar dagen — soms iets later, want Hartverwarmers is een vrijwillig project.
            </p>
        </div>
    @else
        @php($messagePlaceholder = $reason === 'feedback' ? 'Wat vind je nu al fijn? En wat zou je graag beter zien?' : 'Waarmee kunnen we je helpen?')
        <form wire:submit="send" class="space-y-4 mt-6">
            @error('throttle')
                <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-red-700 text-sm">{{ $message }}</p>
                </div>
            @enderror

            <flux:field>
                <flux:label>Waarover gaat het?</flux:label>
                <flux:select wire:model.live="reason" placeholder="Maak een keuze">
                    @foreach (\App\Livewire\SupportContactForm::REASONS as $slug => $label)
                        <flux:select.option value="{{ $slug }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="reason" />
            </flux:field>

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
                <flux:textarea wire:model="message" placeholder="{{ $messagePlaceholder }}" rows="5" />
                <flux:error name="message" />
            </flux:field>

            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Verstuur bericht</span>
                <span wire:loading>Bezig met versturen...</span>
            </flux:button>
        </form>
    @endif
</div>
