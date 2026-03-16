<div>
    @php
        $avatarColors = [
            ['bg' => '#FDF3EE', 'text' => '#E8764B'],
            ['bg' => '#E8F6F8', 'text' => '#3A9BA8'],
            ['bg' => '#FEF6E0', 'text' => '#B08A22'],
            ['bg' => '#F3E8F3', 'text' => '#9A5E98'],
        ];
        $color = $avatarColors[$colorIndex] ?? $avatarColors[0];
    @endphp

    <div class="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-6 mb-8">
        <flux:file-upload wire:model="photo" accept="image/jpeg,image/png,image/webp">
            <div class="relative flex items-center justify-center size-20 shrink-0 rounded-full cursor-pointer transition-all hover:scale-105"
                 @if(!$photo && !$existingAvatar)
                     style="background: {{ $color['bg'] }}; color: {{ $color['text'] }};"
                 @endif
            >
                @if ($photo && $photo->isPreviewable())
                    <img src="{{ $photo->temporaryUrl() }}" class="size-full object-cover rounded-full" />
                @elseif ($existingAvatar)
                    <img src="{{ $existingAvatarUrl }}" class="size-full object-cover rounded-full" />
                @else
                    <span class="text-2xl font-bold">{{ $initials }}</span>
                @endif

                <div class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-white border border-[var(--color-border-light)] shadow-sm flex items-center justify-center text-[var(--color-text-secondary)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                </div>
            </div>
        </flux:file-upload>

        <div class="flex flex-col items-center sm:items-start gap-1">
            <p class="text-sm text-[var(--color-text-secondary)]">Klik op de avatar om een foto te uploaden</p>

            @if ($existingAvatar)
                <button wire:click="deleteAvatar" wire:confirm="Weet je zeker dat je je profielfoto wilt verwijderen?" type="button" class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-700 underline underline-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    Foto verwijderen
                </button>
            @endif
        </div>
    </div>

    @error('photo')
        <div class="text-red-600 text-sm mb-4">{{ $message }}</div>
    @enderror
</div>
