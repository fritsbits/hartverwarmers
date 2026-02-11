<x-layout title="Mijn profiel">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <h1 class="text-3xl font-bold mb-8">Mijn profiel</h1>

        <flux:card>
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-2xl font-semibold">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <flux:heading size="lg">{{ $user->name }}</flux:heading>
                    <flux:text class="text-[var(--color-text-secondary)]">{{ $user->email }}</flux:text>
                </div>
            </div>

            <flux:separator class="my-6" />

            <div class="grid gap-4">
                <flux:button variant="ghost" href="{{ route('profile.bookmarks') }}" icon="bookmark" class="justify-start">
                    Mijn bookmarks
                </flux:button>
            </div>
        </flux:card>
    </div>
</x-layout>
