<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Hartverwarmers') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:300,400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="font-body antialiased min-h-screen bg-[var(--color-bg-cream)]">
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        {{-- Left panel: hero image (desktop only) --}}
        <div class="hidden lg:block lg:relative border-r border-[var(--color-border-light)]">
            <img
                src="/images/hero-auth.webp"
                alt="Welkom bij Hartverwarmers — foto's van lachende bewoners, een kopje koffie en een sleutel"
                class="absolute inset-0 h-full w-full object-cover"
            >
        </div>

        {{-- Right panel: hero zone + form zone --}}
        <div class="flex flex-col min-h-screen">
            {{-- Hero zone (cream, 1/3 height, content bottom-aligned) --}}
            <div class="flex flex-col justify-end h-1/3 px-8 pt-8 pb-8 sm:px-12 border-b border-[var(--color-border-light)]">
                {{ $header }}
            </div>

            {{-- Form zone (white, 2/3 height, content top-aligned) --}}
            <div class="bg-[var(--color-bg-white)] h-2/3 px-8 pt-10 sm:px-12">
                <div class="max-w-lg">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>
