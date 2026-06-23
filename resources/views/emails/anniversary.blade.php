@component('mail::message')
Hoi {{ $notifiable->first_name }},

Vandaag precies {{ $year === 1 ? 'één' : $year }} jaar geleden deelde je je eerste fiche op Hartverwarmers{{ $payload->firstFicheTitle ? ': **'.$payload->firstFicheTitle.'**' : '' }}.

@if($payload->firstFicheTheme)
Een idee over {{ $payload->firstFicheTheme }} — precies het soort moment waar Hartverwarmers om draait. Bedankt dat je het toen deelde.
@else
Precies het soort moment waar Hartverwarmers om draait. Bedankt dat je het toen deelde.
@endif

Er is sindsdien veel gebeurd: collega's uit heel Vlaanderen delen intussen honderden fiches. We zouden het fijn vinden om opnieuw iets van jou te zien.

@component('mail::button', ['url' => \App\Support\EmailLink::to(route('fiches.create'), 'anniversary', 'lifecycle', 'primary')])
Deel je volgende fiche
@endcomponent

@if($payload->firstFicheInitiativeSlug)
Nog niet meteen iets te delen? Geen zorgen — kijk gerust eens naar [andere uitwerkingen rond {{ $payload->firstFicheInitiativeName }}]({{ \App\Support\EmailLink::to(route('initiatives.show', $payload->firstFicheInitiativeSlug), 'anniversary', 'lifecycle', 'secondary') }}) voor wat inspiratie.
@endif

Warme groet,
Frederik & Maite van Hartverwarmers

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])
@endcomponent
