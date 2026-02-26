<x-profile-layout title="Mijn profiel">
    <flux:card>
        <flux:heading size="lg" class="mb-6">Persoonlijke gegevens</flux:heading>

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
                        <flux:label>Achternaam</flux:label>
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

                <flux:field>
                    <flux:label>Over jou</flux:label>
                    <flux:textarea name="bio" rows="4">{{ old('bio', $user->bio) }}</flux:textarea>
                    <x-input-error :messages="$errors->get('bio')" />
                </flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Website</flux:label>
                        <flux:input type="url" name="website" :value="old('website', $user->website)" placeholder="https://" />
                        <x-input-error :messages="$errors->get('website')" />
                    </flux:field>

                    <flux:field>
                        <flux:label>LinkedIn</flux:label>
                        <flux:input type="url" name="linkedin" :value="old('linkedin', $user->linkedin)" placeholder="https://linkedin.com/in/" />
                        <x-input-error :messages="$errors->get('linkedin')" />
                    </flux:field>
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Opslaan</flux:button>
                </div>
            </div>
        </form>
    </flux:card>
</x-profile-layout>
