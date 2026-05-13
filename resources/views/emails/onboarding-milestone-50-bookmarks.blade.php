@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Wauw — je fiches werden intussen al **{{ $bookmarkCount }} keer** bewaard. Dat is geen toeval: je deelt dingen die écht werken.

@component('mail::button', ['url' => route('contributors.index')])
Bekijk je bijdragen
@endcomponent

Benieuwd welke andere fiches zo populair zijn? Bekijk onze diamantjes — de beste fiches van de community, met de hand uitgekozen.

@component('mail::button', ['url' => url('/diamantjes')])
Bekijk de diamantjes
@endcomponent

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])

Warme groet,
Het Hartverwarmers-team
@endcomponent
