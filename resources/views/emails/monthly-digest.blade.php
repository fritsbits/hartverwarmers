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
    </style>
</head>
<body style="margin:0;padding:0;background:#FEF8F4">

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

                {{-- Themes block (Task 7) --}}
                {{-- Diamond block (Task 8) --}}
                {{-- Recent fiches block (Task 9) --}}
                {{-- Footer (Task 11) --}}

            </table>
        </td>
    </tr>
</table>

</body>
</html>
