<x-sidebar-layout title="E-mails" section-label="Beheer" description="Alle uitgaande e-mails, gegroepeerd per type.">

    <div class="space-y-10">
        @foreach($categories as $category)
            @if($category['emails']->isEmpty())
                @continue
            @endif

            <section>
                <h2 class="section-label mb-3">{{ $category['label'] }}</h2>

                <div class="border border-[var(--color-border-light)] rounded-xl overflow-hidden bg-white">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-border-light)] text-left text-xs uppercase tracking-wider text-[var(--color-text-secondary)]">
                                <th class="px-4 py-3 font-semibold w-1/4">Naam</th>
                                <th class="px-4 py-3 font-semibold">Onderwerp</th>
                                <th class="px-4 py-3 font-semibold w-1/4 whitespace-nowrap">Trigger</th>
                                <th class="px-2 py-3 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border-light)]">
                            @foreach($category['emails'] as $email)
                                <tr class="group hover:bg-[var(--color-bg-accent-light)] transition-colors">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.mails.show', $email['key']) }}" class="font-semibold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors">
                                            {{ $email['label'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-[var(--color-text-secondary)] truncate max-w-0">
                                        <a href="{{ route('admin.mails.show', $email['key']) }}" class="block truncate">
                                            {{ $email['subject'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-[var(--color-text-secondary)] whitespace-nowrap">
                                        {{ $email['trigger'] }}
                                    </td>
                                    <td class="px-2 py-3 text-[var(--color-text-tertiary)] group-hover:text-[var(--color-primary)] transition-colors">
                                        <a href="{{ route('admin.mails.show', $email['key']) }}" class="block px-2" aria-label="Open {{ $email['label'] }}">
                                            &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    <p class="text-xs text-[var(--color-text-tertiary)] mt-10">
        Templates: <span class="font-mono">resources/views/emails/</span>
    </p>

</x-sidebar-layout>
