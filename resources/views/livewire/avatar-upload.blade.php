<div>
    <div class="flex items-center gap-6 mb-8">
        <flux:file-upload wire:model="photo" accept="image/jpeg,image/png,image/webp">
            <div class="
                relative flex items-center justify-center size-20 shrink-0 rounded-full transition-colors cursor-pointer
                border border-zinc-200 dark:border-white/10 hover:border-zinc-300 dark:hover:border-white/10
                bg-zinc-100 hover:bg-zinc-200 dark:bg-white/10 hover:dark:bg-white/15 in-data-dragging:dark:bg-white/15
            ">
                @if ($photo && $photo->isPreviewable())
                    <img src="{{ $photo->temporaryUrl() }}" class="size-full object-cover rounded-full" />
                @elseif ($existingAvatar)
                    <img src="{{ Storage::url($existingAvatar) }}" class="size-full object-cover rounded-full" />
                @else
                    <flux:icon name="user" variant="solid" class="text-zinc-500 dark:text-zinc-400" />
                @endif

                <div class="absolute bottom-0 right-0 bg-white dark:bg-zinc-800 rounded-full">
                    <flux:icon name="arrow-up-circle" variant="solid" class="text-zinc-500 dark:text-zinc-400" />
                </div>
            </div>
        </flux:file-upload>

        <div class="flex flex-col gap-1">
            <p class="text-sm text-[var(--color-text-secondary)]">Klik op de avatar om een foto te uploaden</p>

            @if ($existingAvatar)
                <button wire:click="deleteAvatar" wire:confirm="Weet je zeker dat je je profielfoto wilt verwijderen?" type="button" class="text-sm text-red-600 hover:text-red-700 text-left">
                    Foto verwijderen
                </button>
            @endif
        </div>
    </div>

    @error('photo')
        <div class="text-red-600 text-sm mb-4">{{ $message }}</div>
    @enderror

    @if (session('message'))
        <div class="text-green-600 text-sm mb-4">{{ session('message') }}</div>
    @endif
</div>
