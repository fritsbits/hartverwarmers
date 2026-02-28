<div>
    <span class="section-label">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
        </svg>
        Reacties
    </span>
    <h2 class="mt-1 mb-4">Bedank de auteur of stel een vraag</h2>

    {{-- Existing comments --}}
    @foreach($this->comments as $comment)
        <div wire:key="comment-{{ $comment->id }}" class="flex gap-4 py-4 {{ !$loop->last ? 'border-b border-[var(--color-border-light)]' : '' }}">
            <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center font-semibold shrink-0">
                {{ substr($comment->user->first_name ?? 'A', 0, 1) }}
            </div>
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
                @auth
                    <button wire:click="startReply({{ $comment->id }})" class="mt-2 text-sm font-medium transition-colors" style="color: var(--color-primary)">
                        Reageer
                    </button>
                @endauth

                {{-- Replies --}}
                @if($comment->replies->isNotEmpty())
                    <div class="mt-4 space-y-4 pl-2 border-l-2 border-[var(--color-border-light)]">
                        @foreach($comment->replies as $reply)
                            <div wire:key="reply-{{ $reply->id }}" class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-[var(--color-secondary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                                    {{ substr($reply->user->first_name ?? 'A', 0, 1) }}
                                </div>
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
                    <div class="mt-4 pl-2 border-l-2 border-[var(--color-primary)]">
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                                {{ substr(auth()->user()->first_name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <textarea
                                    wire:model="replyBody"
                                    placeholder="Schrijf een reactie..."
                                    rows="2"
                                    class="w-full rounded-lg border border-[var(--color-border-light)] bg-white px-3 py-2 text-sm placeholder:text-[var(--color-text-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-y"
                                ></textarea>
                                @error('replyBody') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                <div class="mt-2 flex items-center gap-2">
                                    <flux:button wire:click="addReply" variant="primary" size="xs">Plaats reactie</flux:button>
                                    <flux:button wire:click="cancelReply" variant="ghost" size="xs">Annuleren</flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Comment form --}}
    @auth
        <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 {{ $this->comments->isNotEmpty() ? 'mt-8' : '' }}">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                    {{ substr(auth()->user()->first_name, 0, 1) }}
                </div>
                <span class="text-sm font-medium">{{ auth()->user()->full_name }}</span>
            </div>
            <form wire:submit="addComment">
                <textarea
                    wire:model="body"
                    placeholder="Bedank de auteur, stel een vraag of deel een tip..."
                    rows="3"
                    class="w-full rounded-lg border border-[var(--color-border-light)] bg-white px-4 py-3 text-sm placeholder:text-[var(--color-text-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-y"
                ></textarea>
                @error('body') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                <div class="mt-3 flex items-center {{ $this->comments->isEmpty() ? 'justify-between' : 'justify-end' }}">
                    @if($this->comments->isEmpty())
                        <span class="text-sm text-[var(--color-text-secondary)]">Wees de eerste die reageert.</span>
                    @endif
                    <flux:button type="submit" variant="primary" size="sm">Plaats reactie</flux:button>
                </div>
            </form>
        </div>
    @else
        @if(Route::has('login'))
            <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 mt-8 text-center">
                <a href="{{ route('login') }}" class="cta-link">Log in</a> om te reageren.
            </div>
        @endif
    @endauth
</div>
