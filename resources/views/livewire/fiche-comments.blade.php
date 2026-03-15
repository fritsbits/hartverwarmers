<div>
    <span class="section-label mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
        </svg>
        {{ $this->commentCount }} {{ $this->commentCount === 1 ? 'reactie' : 'reacties' }}
    </span>

    {{-- Existing comments first --}}
    @if($this->comments->isNotEmpty())
        <div class="space-y-0 mb-6">
            @foreach($this->comments as $comment)
                <div wire:key="comment-{{ $comment->id }}" class="flex gap-4 py-5 {{ !$loop->last ? 'border-b border-[var(--color-border-light)]' : '' }}">
                    <x-user-avatar :user="$comment->user" size="md" />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="text-sm">
                                <span class="font-semibold">{{ $comment->user->full_name ?? 'Anoniem' }}</span>
                                @if($comment->user?->organisation)
                                    <span class="text-[var(--color-text-secondary)]"> &middot; {{ $comment->user->organisation }}</span>
                                @endif
                            </div>
                            <span class="text-sm text-[var(--color-text-secondary)] shrink-0">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-2">{{ $comment->body }}</p>
                        <button wire:click="startReply({{ $comment->id }})" class="mt-2 text-sm font-medium transition-colors" style="color: var(--color-primary)">
                            Reageer
                        </button>

                        {{-- Replies --}}
                        @if($comment->replies->isNotEmpty())
                            <div class="mt-4 space-y-4 pl-2 border-l-2 border-[var(--color-border-light)]">
                                @foreach($comment->replies as $reply)
                                    <div wire:key="reply-{{ $reply->id }}" class="flex gap-3">
                                        <x-user-avatar :user="$reply->user" size="sm" />
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="text-sm">
                                                    <span class="font-semibold">{{ $reply->user->full_name ?? 'Anoniem' }}</span>
                                                    @if($reply->user?->organisation)
                                                        <span class="text-[var(--color-text-secondary)]"> &middot; {{ $reply->user->organisation }}</span>
                                                    @endif
                                                </div>
                                                <span class="text-xs text-[var(--color-text-secondary)] shrink-0">{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="mt-1 text-sm">{{ $reply->body }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Inline reply form --}}
                        @if($replyingTo === $comment->id)
                            <div class="mt-4 pl-2 border-l-2 border-[var(--color-primary)]" x-data="{ step: {{ auth()->check() ? '2' : '1' }} }">
                                <div class="flex gap-3">
                                    @auth
                                        <x-user-avatar :user="auth()->user()" size="sm" />
                                    @endauth
                                    <div class="flex-1">
                                        <div x-show="step === 1 || step === 2">
                                            <textarea
                                                wire:model="replyBody"
                                                placeholder="Schrijf een reactie..."
                                                rows="2"
                                                class="w-full rounded-lg border border-[var(--color-border-light)] bg-white px-3 py-2 text-sm placeholder:text-[var(--color-text-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-y"
                                            ></textarea>
                                            @error('replyBody') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                        </div>

                                        @auth
                                            <div class="mt-2 flex items-center gap-2">
                                                <flux:button wire:click="addReply" variant="primary" size="xs">Plaats reactie</flux:button>
                                                <flux:button wire:click="cancelReply" variant="ghost" size="xs">Annuleren</flux:button>
                                            </div>
                                        @else
                                            <div x-show="step === 1" class="mt-2 flex items-center gap-2">
                                                <flux:button variant="primary" size="xs" x-on:click="if ($wire.replyBody.trim().length > 0) step = 2">Plaats reactie</flux:button>
                                                <flux:button wire:click="cancelReply" variant="ghost" size="xs">Annuleren</flux:button>
                                            </div>

                                            <div x-show="step === 2" x-cloak
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0 translate-y-1"
                                                 x-transition:enter-end="opacity-100 translate-y-0"
                                                 class="mt-3 bg-[var(--color-bg-cream)] rounded-lg p-3">
                                                <p class="text-xs font-semibold mb-2 text-[var(--color-text-primary)]">Nog even je naam erbij</p>
                                                <form wire:submit="addGuestReply">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                                                        <div>
                                                            <flux:input wire:model="guestName" placeholder="Je volledige naam" size="sm" />
                                                            @error('guestName') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                                                        </div>
                                                        <div>
                                                            <flux:input wire:model="guestEmail" type="email" placeholder="je@email.be" size="sm" />
                                                            @error('guestEmail') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                        <label class="flex items-start gap-2 text-xs text-[var(--color-text-secondary)]">
                                                            <input type="checkbox" wire:model="guestTerms" class="mt-0.5 rounded border-[var(--color-border-light)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                                                            <span>Ik ga akkoord met de <a href="{{ route('legal.terms') }}" target="_blank" class="underline hover:text-[var(--color-primary)]">gebruiksvoorwaarden</a></span>
                                                        </label>
                                                        <div class="flex items-center gap-2">
                                                            <flux:button wire:click="cancelReply" variant="ghost" size="xs">Annuleren</flux:button>
                                                            <flux:button type="submit" variant="primary" size="xs">Plaats reactie</flux:button>
                                                        </div>
                                                    </div>
                                                    @error('guestTerms') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                                                </form>
                                            </div>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Compact comment form — below comments --}}
    @auth
        <div class="flex gap-3 items-start" x-data="{ focused: false }">
            <x-user-avatar :user="auth()->user()" size="sm" class="mt-0.5" />
            <form wire:submit="addComment" class="flex-1">
                <textarea
                    wire:model="body"
                    placeholder="Schrijf een reactie..."
                    rows="1"
                    x-on:focus="focused = true"
                    class="w-full rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-cream)] px-4 py-2.5 text-sm placeholder:text-[var(--color-text-secondary)]/60 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent focus:bg-white resize-y transition-all"
                    :rows="focused ? 3 : 1"
                ></textarea>
                @error('body') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                <div x-show="focused" x-collapse x-cloak class="mt-2 flex justify-end">
                    <flux:button type="submit" variant="primary" size="sm">Plaats reactie</flux:button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-[var(--color-border-light)] p-6 mb-8"
             x-data="{ step: 1 }">

            {{-- Step dots --}}
            <div class="flex items-center gap-1.5 mb-4">
                <span class="w-2 h-2 rounded-full transition-colors duration-300"
                      :class="step === 1 ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-primary)]/30'"></span>
                <span class="w-2 h-2 rounded-full transition-colors duration-300"
                      :class="step === 2 ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-primary)]/30'"></span>
            </div>

            {{-- Step 1: Write the comment --}}
            <div x-show="step === 1"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0">
                @if($this->comments->isEmpty())
                    <p class="text-lg font-heading font-bold mb-1">Wees de eerste die reageert!</p>
                @else
                    <p class="text-lg font-heading font-bold mb-1">Doe mee aan het gesprek</p>
                @endif
                <p class="text-sm text-[var(--color-text-secondary)] mb-4">Bedank de auteur, stel een vraag of deel een tip.</p>
                <textarea
                    wire:model="guestBody"
                    placeholder="Bedank de auteur, stel een vraag of deel een tip..."
                    rows="3"
                    class="w-full rounded-xl border border-[var(--color-border-light)] bg-[var(--color-bg-cream)] px-4 py-3 placeholder:text-[var(--color-text-secondary)]/60 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent focus:bg-white resize-y transition-all duration-200"
                ></textarea>
                @error('guestBody') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                <div class="mt-3 flex justify-end">
                    <flux:button variant="primary" x-on:click="if ($wire.guestBody.trim().length > 0) step = 2">Plaats reactie</flux:button>
                </div>
            </div>

            {{-- Step 2: Name + email to finalize --}}
            <div x-show="step === 2" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0">
                <div class="flex items-start gap-3 mb-4">
                    <button x-on:click="step = 1" class="mt-0.5 shrink-0 text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <div>
                        <p class="text-lg font-heading font-bold mb-0.5">Nog even je naam erbij</p>
                        <p class="text-sm text-[var(--color-text-secondary)]">Zo weet de auteur wie er reageert.</p>
                    </div>
                </div>

                {{-- Quote-styled preview of what they wrote --}}
                <div class="border-l-3 border-[var(--color-primary)]/40 pl-4 py-1 mb-4">
                    <p class="text-sm text-[var(--color-text-primary)] italic" x-text="$wire.guestBody"></p>
                </div>

                <form wire:submit="addGuestComment">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <flux:input wire:model="guestName" label="Naam" placeholder="Je volledige naam" />
                            @error('guestName') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <flux:input wire:model="guestEmail" label="E-mailadres" type="email" placeholder="je@email.be" />
                            @error('guestEmail') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <label class="flex items-start gap-2 text-sm text-[var(--color-text-secondary)]">
                            <input type="checkbox" wire:model="guestTerms" class="mt-0.5 rounded border-[var(--color-border-light)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                            <span>Ik ga akkoord met de <a href="{{ route('legal.terms') }}" target="_blank" class="underline hover:text-[var(--color-primary)]">gebruiksvoorwaarden</a></span>
                        </label>
                        <flux:button type="submit" variant="primary">Plaats reactie</flux:button>
                    </div>
                    @error('guestTerms') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </form>
            </div>

            @if(Route::has('login'))
                <p class="text-center text-sm text-[var(--color-text-secondary)] mt-4">
                    Al een account? <a href="{{ route('login') }}" class="font-medium underline hover:text-[var(--color-primary)]">Log in</a>
                </p>
            @endif
        </div>
    @endauth
</div>
