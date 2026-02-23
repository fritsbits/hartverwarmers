<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $elaboration->title }} - Hartverwarmers</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@400;600&family=Roboto+Slab:wght@700;900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Fira Sans', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #1F1F1F;
            padding: 2cm;
        }

        h1, h2, h3 {
            font-family: 'Roboto Slab', serif;
            font-weight: 900;
        }

        h1 {
            font-size: 24pt;
            margin-bottom: 0.5cm;
            color: #E84C4F;
        }

        h2 {
            font-size: 14pt;
            margin-top: 1cm;
            margin-bottom: 0.5cm;
            border-bottom: 2px solid #E84C4F;
            padding-bottom: 0.2cm;
        }

        .header {
            border-bottom: 1px solid #E8E8E8;
            padding-bottom: 0.5cm;
            margin-bottom: 1cm;
        }

        .meta {
            margin-top: 0.3cm;
            font-size: 10pt;
            color: #6F6F6F;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3cm;
            margin-top: 0.3cm;
        }

        .tag {
            background: #F5F6F7;
            padding: 0.1cm 0.3cm;
            border-radius: 0.2cm;
            font-size: 9pt;
        }

        .content {
            margin-top: 1cm;
        }

        .content p {
            margin-bottom: 0.5cm;
        }

        .content ul, .content ol {
            margin-left: 1cm;
            margin-bottom: 0.5cm;
        }

        .content li {
            margin-bottom: 0.2cm;
        }

        .fiche-box {
            background: #F5F6F7;
            padding: 0.5cm;
            margin-top: 1cm;
            border-radius: 0.2cm;
        }

        .fiche-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5cm;
        }

        .fiche-item {
            font-size: 10pt;
        }

        .fiche-label {
            color: #6F6F6F;
            font-size: 9pt;
        }

        .footer {
            margin-top: 2cm;
            padding-top: 0.5cm;
            border-top: 1px solid #E8E8E8;
            font-size: 9pt;
            color: #6F6F6F;
            text-align: center;
        }

        @media print {
            body {
                padding: 0;
            }

            @page {
                margin: 2cm;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $elaboration->title }}</h1>
        <div class="meta">{{ $initiative->title }}</div>

        @if($elaboration->tags->isNotEmpty())
            <div class="tags">
                @foreach($elaboration->tags as $tag)
                    <span class="tag">{{ $tag->name }}</span>
                @endforeach
            </div>
        @endif
    </header>

    @if($elaboration->fiche)
        <div class="fiche-box">
            <div class="fiche-grid">
                @if($elaboration->fiche['duration'] ?? null)
                    <div class="fiche-item">
                        <div class="fiche-label">Duur</div>
                        <div>{{ $elaboration->fiche['duration'] }}</div>
                    </div>
                @endif

                @if($elaboration->fiche['group_size'] ?? null)
                    <div class="fiche-item">
                        <div class="fiche-label">Groepsgrootte</div>
                        <div>{{ $elaboration->fiche['group_size'] }}</div>
                    </div>
                @endif

                @if($elaboration->fiche['materials'] ?? null)
                    <div class="fiche-item" style="grid-column: span 2;">
                        <div class="fiche-label">Materiaal</div>
                        <div>{{ $elaboration->fiche['materials'] }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="content">
        <h2>Beschrijving</h2>
        {!! $elaboration->description !!}
    </div>

    @if($elaboration->practical_tips)
        <div class="content">
            <h2>Praktische tips</h2>
            {!! $elaboration->practical_tips !!}
        </div>
    @endif

    <footer class="footer">
        <p>hartverwarmers.be &middot; {{ now()->format('d/m/Y') }}</p>
    </footer>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
