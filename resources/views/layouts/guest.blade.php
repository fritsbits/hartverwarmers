<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hartverwarmers') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@200;400;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @fluxAppearance
</head>
<body class="font-body antialiased min-h-screen bg-[var(--color-bg-subtle)]">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="/" class="text-2xl font-bold">
                <span class="text-[var(--color-primary)]">hart</span>verwarmers
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-[var(--color-bg-white)] shadow-md rounded-lg">
            {{ $slot }}
        </div>
    </div>

    @fluxScripts
</body>
</html>
