@php
    $campaign = config('newsletter.milestone_call_mail_key');
    $ownShare = match (true) {
        $ownFichesCount === 1 => ', en één daarvan is van jou',
        $ownFichesCount > 1 => ", en {$ownFichesCount} daarvan zijn van jou",
        default => '',
    };
@endphp
<!DOCTYPE html>
<html lang="nl" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Hartverwarmers · samen naar {{ $content->target }}</title>
    <style>
        body { margin: 0; padding: 0; background: #FEF8F4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #231E1A; }
        a { color: #E8764B; }
        .heading-serif { font-family: Georgia, 'Times New Roman', serif; font-weight: 700; letter-spacing: -0.01em; }
        .meta { color: #756C65; font-size: 13px; }
        .card { background: #FFFFFF; border: 1px solid #EBE4DE; border-radius: 10px; }
        .btn { display: inline-block; background: #E8764B; color: #FFFFFF !important; font-weight: 700; font-size: 15px; padding: 13px 28px; border-radius: 50px; text-decoration: none; }
        @media (prefers-color-scheme: dark) {
            body { background: #FEF8F4 !important; color: #231E1A !important; }
            a { color: #E8764B !important; }
            .meta { color: #756C65 !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#FEF8F4">

<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden">Hartverwarmers telt {{ $content->fichesCount }} activiteiten. Help je mee naar {{ $content->target }}?</div>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#FEF8F4">
    <tr>
        <td align="center" style="padding:32px 16px">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%">

                {{-- Header --}}
                <tr>
                    <td align="center" style="padding:8px 0 32px 0">
                        <img src="{{ asset('img/hartverwarmers-logo-email.png') }}" alt="Hartverwarmers" width="40" height="40" style="display:block;border:0;margin:0 auto 8px auto">
                        <div class="heading-serif" style="font-size:26px;color:#231E1A;line-height:1.1">Hartverwarmers</div>
                        <div style="color:#756C65;font-size:12px;margin-top:6px">voor en door animatoren in de zorg</div>
                    </td>
                </tr>

                {{-- Intro --}}
                <tr>
                    <td style="padding:0 0 28px 0;line-height:1.65;font-size:15px">
                        <p style="margin:0 0 14px 0"><strong>Hoi {{ $notifiable->first_name }},</strong></p>
                        <p style="margin:0 0 14px 0">Hartverwarmers telt nu <strong>{{ $content->fichesCount }} activiteiten</strong>{{ $ownShare }}. Bedankt!</p>
                        <p style="margin:0">Halen we samen de {{ $content->target }}? Dan zijn er nog {{ $content->remaining() }} nodig.</p>
                    </td>
                </tr>

                {{-- Where it is thin --}}
                <tr>
                    <td style="padding:0 0 28px 0;line-height:1.65;font-size:15px">
                        @if ($content->richest->isNotEmpty())
                            <p style="margin:0 0 14px 0">Bij sommige activiteiten is er al veel keuze, zoals {{ $content->richest->map(fn ($i) => "{$i->title} ({$i->fiches_count})")->join(', ', ' en ') }}. Bij andere vinden collega's nog bijna niets:</p>
                        @endif

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="card" style="background:#FFFFFF;border:1px solid #EBE4DE;border-radius:10px">
                            @foreach ($content->needsLove as $initiative)
                                <tr>
                                    <td style="padding:12px 16px;{{ ! $loop->last ? 'border-bottom:1px solid #EBE4DE;' : '' }}">
                                        <a href="{{ \App\Support\NewsletterLink::tracked($notifiable, \App\Support\EmailLink::to(route('initiatives.show', $initiative->slug), $campaign, 'newsletter', 'needs-love')) }}" style="text-decoration:none;color:inherit;display:block">
                                            <span style="font-weight:600;font-size:14px">{{ $initiative->title }}</span>
                                            <span class="meta">&middot; {{ $initiative->fiches_count }} {{ $initiative->fiches_count === 1 ? 'fiche' : 'fiches' }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>

                        <p style="margin:14px 0 0 0">Doe jij zoiets met je bewoners? Een paar zinnen over wat je doet en voor wie het werkt, meer is niet nodig.</p>
                    </td>
                </tr>

                {{-- CTA --}}
                <tr>
                    <td style="padding:0 0 36px 0;text-align:center">
                        <a href="{{ \App\Support\NewsletterLink::tracked($notifiable, \App\Support\EmailLink::to(route('fiches.create'), $campaign, 'newsletter', 'create-fiche')) }}" class="btn" style="display:inline-block;background:#E8764B;color:#FFFFFF;font-weight:700;font-size:15px;padding:13px 28px;border-radius:50px;text-decoration:none">Deel een activiteit &rarr;</a>
                    </td>
                </tr>

                {{-- Signoff --}}
                <tr>
                    <td style="padding:0 0 24px 0;line-height:1.6;font-size:15px;color:#231E1A">
                        <p style="margin:0">Warme groet,<br>Het Hartverwarmers-team</p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:24px 0 0 0;border-top:1px solid #EBE4DE;text-align:center;color:#756C65;font-size:12px;line-height:1.6">
                        <p style="margin:0 0 8px 0">Hartverwarmers · voor en door animatoren in de zorg</p>
                        <p style="margin:0">Je krijgt deze e-mail omdat je een activiteit deelde op Hartverwarmers.<br>
                            <a href="{{ route('profile.notifications') }}" style="color:#756C65;text-decoration:underline">Meldingen beheren</a>
                            &middot;
                            <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('newsletter.unsubscribe', ['user' => $notifiable->id]) }}" style="color:#756C65;text-decoration:underline">Uitschrijven</a>
                        </p>
                        <p style="margin:8px 0 0 0;font-size:11px">Hartverwarmers · {{ config('mail.from.postal_address') }}</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
