@props(['title' => null])
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' : '' }}{{ config('app.name', 'Hartverwarmers') }}</title>
    <meta name="description" content="Ontdek deugddoende activiteiten voor woonzorgcentra. Hartverwarmers is het platform waar activiteitenbegeleiders praktijkfiches delen.">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ? $title . ' — ' . config('app.name') : config('app.name', 'Hartverwarmers') }}">
    <meta property="og:description" content="Ontdek deugddoende activiteiten voor woonzorgcentra. Hartverwarmers is het platform waar activiteitenbegeleiders praktijkfiches delen.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/hero-bewoonster-en-verzorgster-babbelen.jpg') }}">
    <meta property="og:locale" content="nl_BE">
    <meta property="og:site_name" content="{{ config('app.name', 'Hartverwarmers') }}">
    <link rel="icon" type="image/svg+xml" href="/img/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:300,400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="font-body antialiased min-h-screen bg-[var(--color-bg-cream)]">
    <flux:toast position="top end" />

    @if(session('toast'))
        <div x-data x-init="$flux.toast(@js(session('toast')))"></div>
    @elseif(session('success'))
        <div x-data x-init="$flux.toast({ text: @js(session('success')), variant: 'success' })"></div>
    @endif

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
            {{-- Hero zone (cream, fixed height, content bottom-aligned) --}}
            <div class="flex flex-col justify-end min-h-[280px] px-8 pt-8 pb-8 sm:px-12 border-b border-[var(--color-border-light)]">
                {{ $header }}
            </div>

            {{-- Form zone (white, fills remaining height, content top-aligned) --}}
            <div class="bg-[var(--color-bg-white)] flex-1 px-8 pt-10 sm:px-12">
                <div class="max-w-lg">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>
