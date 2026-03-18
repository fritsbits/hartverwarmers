<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Er is iets misgegaan — Hartverwarmers</title>

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
        .error-layout {
            display: grid;
            min-height: 100vh;
            grid-template-columns: 1fr;
        }
        @media (min-width: 768px) {
            .error-layout {
                grid-template-columns: 1fr 1fr;
            }
        }
        .error-image-panel {
            display: none;
            position: relative;
            border-right: 1px solid var(--color-border-light, #EBE4DE);
        }
        @media (min-width: 768px) {
            .error-image-panel {
                display: block;
            }
        }
        .error-image-panel img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        .error-content-panel {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .error-center {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background-color: var(--color-bg-cream, #FEF8F4);
        }
        .error-content {
            max-width: 28rem;
            width: 100%;
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
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s;
        }
        .btn-primary:hover {
            background: var(--color-primary-hover, #D4683F);
        }
        .cta-link {
            color: var(--color-primary, #E8764B);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .error-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .error-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-layout">
        <div class="error-image-panel">
            <img
                src="/images/error-illustration.webp"
                alt="Een vergrootglas, puzzelstukjes en een kaartje met 'Oooops'"
            >
        </div>

        <div class="error-content-panel">
            <div class="error-center">
                <div class="error-content">
                    <span class="section-label">Fout 500</span>
                    <h1>Er is iets misgegaan</h1>
                    <p>Dit is niet jouw fout — er is een technisch probleem aan onze kant. Probeer de pagina opnieuw te laden. Als het probleem blijft, stuur ons een mailtje en we lossen het zo snel mogelijk op.</p>
                    <div class="error-actions">
                        <button onclick="window.location.reload()" class="btn-primary">Probeer opnieuw</button>
                        <a href="mailto:info@hartverwarmers.be" class="cta-link">Stuur een mailtje naar info@hartverwarmers.be</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
