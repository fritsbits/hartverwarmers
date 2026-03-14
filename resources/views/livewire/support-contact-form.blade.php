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
