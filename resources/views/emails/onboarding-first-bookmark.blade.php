@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Goed nieuws: iemand heeft je fiche **{{ $fiche->title }}** bewaard. Ze willen het gebruiken met hun bewoners.

@component('mail::button', ['url' => \App\Support\EmailLink::to(route('fiches.show', [$fiche->initiative, $fiche]), 'onboarding-first-bookmark', 'lifecycle')])
Bekijk je fiche
@endcomponent

Bedankt dat je deelt. Dit is precies waarom Hartverwarmers bestaat.

Warme groet,
Het Hartverwarmers-team

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])
@endcomponent
