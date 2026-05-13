<!DOCTYPE html>
<html lang="nl" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Hartverwarmers · maandelijkse update</title>
    <style>
        body { margin: 0; padding: 0; background: #FEF8F4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #231E1A; }
        a { color: #E8764B; }
        .heading-serif { font-family: Georgia, 'Times New Roman', serif; font-weight: 700; letter-spacing: -0.01em; }
        .section-label { color: #E8764B; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; }
        .meta { color: #756C65; font-size: 13px; }
        .card { background: #FFFFFF; border: 1px solid #EBE4DE; border-radius: 10px; }
        @media (max-width: 600px) {
            .fiche-cell { display: block !important; width: 100% !important; }
        }
        @media (prefers-color-scheme: dark) {
            body { background: #FEF8F4 !important; color: #231E1A !important; }
            a { color: #E8764B !important; }
            .meta { color: #756C65 !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#FEF8F4">

<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all">{{ $previewText }}</div>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#FEF8F4">
    <tr>
        <td align="center" style="padding:32px 16px">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%">

                {{-- Header --}}
                <tr>
                    <td align="center" style="padding:8px 0 32px 0">
                        <img src="{{ asset('img/hartverwarmers-logo-email.png') }}" alt="Hartverwarmers" width="40" height="40" style="display:block;border:0;margin:0 auto 8px auto">
                        <div class="heading-serif" style="font-size:26px;color:#231E1A;line-height:1.1">Hartverwarmers</div>
                        <div style="color:#756C65;font-size:12px;margin-top:6px">jouw maandelijkse update</div>
                    </td>
                </tr>

                {{-- Intro --}}
                <tr>
                    <td style="padding:0 0 36px 0;line-height:1.6;font-size:15px">
                        <p style="margin:0 0 12px 0"><strong>Hoi {{ $notifiable->first_name }},</strong></p>
                        @include('emails.partials.monthly-digest-intro-line', ['payload' => $payload])
                    </td>
                </tr>

                @if ($payload->themes->isNotEmpty())
                <tr>
                    <td style="padding:0 0 36px 0">
                        <div class="section-label" style="margin-bottom:14px">De komende weken</div>
                        <div class="heading-serif" style="font-size:22px;color:#231E1A;margin-bottom:18px">Thema's om alvast in te plannen</div>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="card" style="background:#FFFFFF;border:1px solid #EBE4DE;border-radius:10px">
                            @foreach ($payload->themes as $occurrence)
                                <tr>
                                    <td style="padding:14px 16px;{{ ! $loop->last ? 'border-bottom:1px solid #EBE4DE;' : '' }}">
                                        <a href="{{ url('/themas#thema-' . $occurrence->theme->slug) }}" style="text-decoration:none;color:inherit;display:block">
                                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tr>
                                                    <td width="80" valign="middle" style="padding-right:14px">
                                                        <div class="heading-serif" style="background:#FDF3EE;color:#E8764B;font-size:12px;padding:6px 10px;border-radius:6px;text-align:center;white-space:nowrap">{{ $occurrence->start_date->locale('nl_BE')->isoFormat('D MMM') }}</div>
                                                    </td>
                                                    <td valign="middle">
                                                        <div style="font-weight:600;font-size:14px">{{ $occurrence->theme->title }}</div>
                                                        <div class="meta">{{ $occurrence->theme->fiches_count ?? 0 }} fiches beschikbaar</div>
                                                    </td>
                                                    <td valign="middle" width="20" align="right" style="color:#E8764B;font-size:18px">&rarr;</td>
                                                </tr>
                                            </table>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                @endif
                @if ($payload->diamond)
                <tr>
                    <td style="padding:0 0 36px 0">
                        <div class="section-label" style="margin-bottom:14px">&#9733; Diamantje van de maand</div>

                        <a href="{{ route('fiches.show', [$payload->diamond->initiative, $payload->diamond]) }}" style="text-decoration:none;color:inherit;display:block">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="card" style="background:#FFFFFF;border:1px solid #EBE4DE;border-radius:10px">
                                <tr>
                                    <td style="padding:22px">
                                        <div class="heading-serif" style="font-size:21px;line-height:1.25;margin-bottom:10px">{{ $payload->diamond->title }}</div>
                                        <div class="meta" style="margin-bottom:14px">door {{ $payload->diamond->user->full_name }}@if ($payload->diamond->user->organisation) · {{ $payload->diamond->user->organisation }}@endif</div>
                                        <p style="margin:0 0 16px 0;color:#231E1A;font-size:14px;line-height:1.6">{{ \Illuminate\Support\Str::limit(strip_tags($payload->diamond->description), 180) }}</p>
                                        <div style="color:#E8764B;font-weight:600;font-size:14px">Lees de fiche &rarr;</div>
                                    </td>
                                </tr>
                            </table>
                        </a>
                    </td>
                </tr>
                @endif
                @if ($payload->recentFiches->isNotEmpty())
                <tr>
                    <td style="padding:0 0 36px 0">
                        <div class="section-label" style="margin-bottom:14px">Recent gedeeld</div>
                        <div class="heading-serif" style="font-size:22px;color:#231E1A;margin-bottom:6px">Fiches uit andere woonzorgcentra</div>
                        <p class="meta" style="margin:0 0 18px 0">Pak wat past, pas aan, deel terug.</p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            @foreach ($payload->recentFiches->chunk(2) as $row)
                                <tr>
                                    @foreach ($row as $fiche)
                                        <td class="fiche-cell" valign="top" width="50%" style="padding:0 5px 10px 0">
                                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" style="text-decoration:none;color:inherit;display:block">
                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="card" style="background:#FFFFFF;border:1px solid #EBE4DE;border-radius:8px">
                                                    <tr>
                                                        <td style="padding:14px">
                                                            <div class="heading-serif" style="font-size:15px;line-height:1.3;margin-bottom:6px">{{ $fiche->title }}</div>
                                                            <div style="color:#756C65;font-size:12px">{{ $fiche->user->first_name }}@if ($fiche->user->organisation) · {{ $fiche->user->organisation }}@endif</div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </a>
                                        </td>
                                    @endforeach
                                    @if ($row->count() === 1)
                                        <td width="50%"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                @endif
                {{-- Signoff --}}
                <tr>
                    <td style="padding:0 0 24px 0;line-height:1.6;font-size:15px;color:#231E1A">
                        <p style="margin:0">Warme groet,<br>Het Hartverwarmers-team</p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:24px 0 0 0;border-top:1px solid #EBE4DE;text-align:center;color:#756C65;font-size:12px;line-height:1.6">
                        <p style="margin:0 0 8px 0;color:#231E1A;font-size:14px">Heb je zelf iets moois op poten gezet?<br>
                            <a href="{{ url('/fiches/nieuw') }}" style="color:#E8764B;font-weight:600;text-decoration:none">Deel jouw fiche &rarr;</a>
                        </p>
                        <p style="margin:18px 0 4px 0">Hartverwarmers · jouw maandelijkse update</p>
                        <p style="margin:0">Je krijgt deze e-mail omdat je een account hebt op Hartverwarmers.<br>
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
