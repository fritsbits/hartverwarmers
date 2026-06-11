@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Wauw — je fiches werden intussen al **{{ $bookmarkCount }} keer** bewaard. Dat is geen toeval: je deelt dingen die écht werken.

@component('mail::button', ['url' => \App\Support\EmailLink::to(route('contributors.index'), 'onboarding-50-bookmarks', 'lifecycle', 'contributors')])
Bekijk je bijdragen
@endcomponent

Benieuwd welke andere fiches zo populair zijn? Bekijk onze diamantjes — de beste fiches van de community, met de hand uitgekozen.

@component('mail::button', ['url' => \App\Support\EmailLink::to(url('/diamantjes'), 'onboarding-50-bookmarks', 'lifecycle', 'diamantjes')])
Bekijk de diamantjes
@endcomponent

Warme groet,
Het Hartverwarmers-team

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])
@endcomponent
