<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina niet gevonden — Hartverwarmers</title>

    <link rel="icon" type="image/svg+xml" href="/img/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:300,400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body {
            font-family: 'Fira Sans', system-ui, sans-serif;
            margin: 0;
            color: var(--color-text-primary, #231E1A);
            background-color: var(--color-bg-white, #FFFFFF);
            -webkit-font-smoothing: antialiased;
        }
        .error-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .error-topbar {
            padding: 1.25rem 1.5rem;
        }
        .error-topbar a {
            font-family: 'Aleo', system-ui, serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--color-text-primary, #231E1A);
            text-decoration: none;
        }
        .error-hero {
            background-color: var(--color-bg-cream, #FEF8F4);
            border-bottom: 1px solid var(--color-border-light, #EBE4DE);
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 1.5rem;
        }
        .error-content {
            max-width: 32rem;
        }
        .section-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--color-primary, #E8764B);
        }
        .error-content h1 {
            font-family: 'Aleo', system-ui, serif;
            font-weight: 700;
            font-size: 2rem;
            line-height: 1.2;
            color: var(--color-text-primary, #231E1A);
            margin: 0.5rem 0 0;
        }
        .error-content p {
            color: var(--color-text-secondary, #756C65);
            font-size: 1.05rem;
            line-height: 1.7;
            margin: 1.25rem 0 2rem;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            background: var(--color-primary, #E8764B);
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn-primary:hover {
            background: var(--color-primary-hover, #D4683F);
        }
        @media (min-width: 640px) {
            .error-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-topbar">
            <a href="/">Hartverwarmers</a>
        </div>

        <section class="error-hero">
            <div class="error-content">
                <span class="section-label">Fout 404</span>
                <h1>Deze pagina bestaat niet meer</h1>
                <p>De link die je volgde is mogelijk verouderd of de pagina heeft een nieuw adres gekregen. Geen zorgen — van hier kom je makkelijk terug.</p>
                <a href="/" class="btn-primary">Ga naar de startpagina</a>
            </div>
        </section>
    </div>
</body>
</html>
