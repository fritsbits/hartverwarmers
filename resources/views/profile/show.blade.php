<x-layout title="Mijn profiel">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <h1 class="text-3xl font-bold mb-8">Mijn profiel</h1>

        <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
                <div class="flex items-center gap-4 mb-6">
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content rounded-full w-16">
                            <span class="text-2xl">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold">{{ $user->name }}</h2>
                        <p class="text-base-content/60">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="grid gap-4">
                    <a href="{{ route('profile.bookmarks') }}" class="btn btn-outline justify-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        Mijn bookmarks
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>
