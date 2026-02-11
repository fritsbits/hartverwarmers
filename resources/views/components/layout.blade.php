<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Hartverwarmers' }} - Hartverwarmers</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@200;400;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @fluxAppearance
</head>
<body class="font-body antialiased min-h-screen bg-[var(--color-bg-subtle)]">
    <!-- Navigation -->
    <x-nav />

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="text-center p-10 bg-[var(--color-bg-subtle)] mt-16">
        <div>
            <p class="font-semibold text-lg">hartverwarmers</p>
            <p class="text-[var(--color-text-secondary)]">Deugddoende activiteiten voor ouderen</p>
            <p class="text-sm text-[var(--color-text-secondary)] mt-2">© {{ date('Y') }} Hartverwarmers. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
