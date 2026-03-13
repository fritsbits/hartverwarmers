<x-sidebar-layout title="Persoonlijke info" section-label="Profiel" description="Beheer je persoonlijke gegevens en voorkeuren.">
    <x-slot:headerAction>
        <a href="{{ route('contributors.show', $user) }}" class="inline-flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors whitespace-nowrap">
            <flux:icon name="arrow-top-right-on-square" variant="mini" class="size-4" />
            Bekijk publiek profiel
        </a>
    </x-slot:headerAction>
    @php
        $missing = collect();
        if (! $user->avatar_path) $missing->push('Voeg een profielfoto toe');
        if (! $user->bio) $missing->push('Schrijf een korte bio');
        if (! $user->function_title) $missing->push('Vul je jobfunctie in');
        if (! $user->organisation) $missing->push('Voeg je organisatie toe');
    @endphp
    @if($missing->isNotEmpty() && $user->fiches()->where('published', true)->exists())
        <div class="flex items-start gap-3 p-4 rounded-xl bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-primary)] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
            <div>
                <p class="text-sm font-medium text-[var(--color-text-primary)]">Maak je bijdragerprofiel compleet</p>
                <ul class="mt-1.5 space-y-1">
                    @foreach($missing as $item)
                        <li class="text-sm text-[var(--color-text-secondary)] flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--color-primary)] shrink-0"></span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <flux:card>

            {{-- Avatar section --}}
            <livewire:avatar-upload />

            {{-- Profile form --}}
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Voornaam</flux:label>
                            <flux:input name="first_name" :value="old('first_name', $user->first_name)" required />
                            <x-input-error :messages="$errors->get('first_name')" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Familienaam</flux:label>
                            <flux:input name="last_name" :value="old('last_name', $user->last_name)" required />
                            <x-input-error :messages="$errors->get('last_name')" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>E-mailadres</flux:label>
                        <flux:input type="email" name="email" :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" />
                    </flux:field>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Jobfunctie</flux:label>
                            <flux:input name="function_title" :value="old('function_title', $user->function_title)" />
                            <x-input-error :messages="$errors->get('function_title')" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Organisatie</flux:label>
                            <flux:input name="organisation" :value="old('organisation', $user->organisation)" />
                            <x-input-error :messages="$errors->get('organisation')" />
                        </flux:field>
                    </div>

                    <div>
                        <flux:label class="mb-1">Over jou</flux:label>
                        <p class="text-[var(--color-text-secondary)] text-sm font-light mb-3">Andere hartverwarmers willen weten wie er achter je fiches zit. Denk aan: hoe lang werk je al in de ouderenzorg, welke activiteiten je het liefst organiseert, of met welke bewoners je werkt.</p>
                        <flux:textarea name="bio" rows="4" placeholder="Bv. Ik werk al 8 jaar als animator in de ouderenzorg...">{{ old('bio', $user->bio) }}</flux:textarea>
                        <x-input-error :messages="$errors->get('bio')" />
                    </div>

                    <flux:field>
                        <flux:label>Website</flux:label>
                        <flux:input type="url" name="website" :value="old('website', $user->website)" placeholder="https://" />
                        <x-input-error :messages="$errors->get('website')" />
                    </flux:field>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">Opslaan</flux:button>
                    </div>
                </div>
            </form>
    </flux:card>
</x-sidebar-layout>
