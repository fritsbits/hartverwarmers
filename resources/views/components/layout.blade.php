<!DOCTYPE html>
<html lang="nl" data-theme="hartverwarmers">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Hartverwarmers' }} - Hartverwarmers</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alan+Sans:wght@700&family=Fira+Sans:wght@200;400;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200">
    <!-- Navigation -->
    <x-nav />

    <!-- Main Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="footer footer-center p-10 bg-base-200 text-base-content mt-16">
        <aside>
            <p class="font-semibold text-lg">hartverwarmers</p>
            <p>Deugddoende activiteiten voor ouderen</p>
            <p class="text-sm text-base-content/60">© {{ date('Y') }} Hartverwarmers. Alle rechten voorbehouden.</p>
        </aside>
    </footer>
</body>
</html>
