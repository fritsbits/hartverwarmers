@component('mail::message')
Hoi {{ $notifiable->first_name }}!

We hebben voor Hartverwarmers een kleine selectie fiches samengesteld die ons écht bijgebleven zijn — fiches die goed uitgewerkt zijn, die animatoren inspireren, en die het beste tonen wat de community te bieden heeft. Wij noemen ze onze **diamantjes**.

@foreach($fiches as $fiche)
@component('mail::panel')
**[{{ $fiche->title }}]({{ \App\Support\EmailLink::to(route('fiches.show', [$fiche->initiative, $fiche]), 'onboarding-curated', 'lifecycle', 'fiche') }})**

*Door {{ $fiche->user?->full_name ?? 'een animator' }}*

{{ str(strip_tags($fiche->description ?? ''))->limit(120) }}

[Bekijk deze fiche]({{ \App\Support\EmailLink::to(route('fiches.show', [$fiche->initiative, $fiche]), 'onboarding-curated', 'lifecycle', 'fiche') }})
@endcomponent

@endforeach

@component('mail::button', ['url' => \App\Support\EmailLink::to(url('/diamantjes'), 'onboarding-curated', 'lifecycle', 'diamantjes')])
Bekijk alle diamantjes
@endcomponent

Warme groet,
Het Hartverwarmers-team

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'onboarding'])
@endcomponent
