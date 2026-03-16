<x-layout title="Gebruikers" :full-width="true">
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <span class="section-label">Admin</span>
            <h1 class="text-3xl mt-1">Gebruikers</h1>
        </div>
    </section>

    <section>
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border-light)] text-left text-[var(--color-text-secondary)]">
                            <th class="pb-3 font-medium">Gebruiker</th>
                            <th class="pb-3 font-medium">E-mail</th>
                            <th class="pb-3 font-medium">Rol</th>
                            <th class="pb-3 font-medium">Organisatie</th>
                            <th class="pb-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr class="border-b border-[var(--color-border-light)]">
                                <td class="py-3">
                                    <div class="flex items-center gap-3">
                                        <x-user-avatar :user="$user" size="sm" />
                                        <span class="font-medium">{{ $user->full_name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 text-[var(--color-text-secondary)]">{{ $user->email }}</td>
                                <td class="py-3">
                                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full
                                        {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $user->role === 'curator' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $user->role === 'contributor' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $user->role === 'member' ? 'bg-gray-100 text-gray-800' : '' }}
                                    ">{{ $user->role }}</span>
                                </td>
                                <td class="py-3 text-[var(--color-text-secondary)]">{{ $user->organisation ?? '—' }}</td>
                                <td class="py-3 text-right">
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.impersonate.start', $user) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-[var(--color-primary)] hover:underline cursor-pointer">
                                                Nabootsen
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layout>
