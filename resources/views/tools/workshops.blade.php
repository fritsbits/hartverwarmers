<x-layout title="Workshops">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('tools.index') }}" class="hover:text-[var(--color-primary)]">Tools & inspiratie</a></li>
                <li>/</li>
                <li class="text-[var(--color-text-primary)] font-medium">Workshops</li>
            </ol>
        </nav>

        <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Workshops</p>
        <h1 class="text-5xl mt-1">Doe-het-zelf-workshops Wonen &amp; leven in het woonzorgcentrum</h1>
        <p class="text-2xl text-[var(--color-text-secondary)] mt-4 mb-10 pb-6 border-b border-[var(--color-border-light)]">Geef je team extra steun om persoonsgericht te werken. Geef hen deze volledig uitgewerkte workshops, telkens met stappenplan en werkbladen</p>

        {{-- Visie --}}
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Visie</h2>
        <p class="text-[var(--color-text-secondary)] mb-6">Krijg de neuzen in dezelfde richting</p>

        @foreach($workshopsVisie as $workshop)
            @include('tools._workshop', ['workshop' => $workshop])
        @endforeach

        <hr class="my-10 border-[var(--color-border-light)]">

        {{-- Proces --}}
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Proces</h2>
        <p class="text-[var(--color-text-secondary)] mb-6">Evalueer jullie aanpak en stuur bij</p>

        @foreach($workshopsProces as $workshop)
            @include('tools._workshop', ['workshop' => $workshop])
        @endforeach

        <hr class="my-10 border-[var(--color-border-light)]">

        {{-- Activiteiten --}}
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Invulling activiteiten</h2>
        <p class="text-[var(--color-text-secondary)] mb-6">Vernieuw het aanbod</p>

        @foreach($workshopsActiviteiten as $workshop)
            @include('tools._workshop', ['workshop' => $workshop])
        @endforeach
    </div>
</x-layout>
