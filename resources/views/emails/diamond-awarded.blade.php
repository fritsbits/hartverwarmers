@component('mail::message')
Hoi {{ $notifiable->first_name }},

Goed nieuws — we hebben jouw fiche **{{ $fiche->title }}** uitgekozen als diamantje. Diamantjes zijn fiches die we extra in de kijker zetten omdat ze diepgaand zijn uitgewerkt, écht werken bij bewoners, en andere animatoren inspireren.

@component('mail::button', ['url' => \App\Support\EmailLink::to(route('fiches.show', [$fiche->initiative, $fiche]), 'diamond-awarded', 'transactional', 'fiche')])
Bekijk je fiche
@endcomponent

[Alle diamantjes ontdekken →]({{ \App\Support\EmailLink::to(route('diamantjes.index'), 'diamond-awarded', 'transactional', 'diamantjes') }})

Bedankt om dit te delen. Dit is precies waarom Hartverwarmers bestaat.

Warme groet,
Frederik & Maite van Hartverwarmers

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])
@endcomponent
