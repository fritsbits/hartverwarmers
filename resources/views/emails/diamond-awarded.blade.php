@component('mail::message')
Hoi {{ $notifiable->first_name }},

Goed nieuws — we hebben jouw fiche **{{ $fiche->title }}** uitgekozen als diamantje. Diamantjes zijn fiches die we extra in de kijker zetten omdat ze diepgaand zijn uitgewerkt, écht werken bij bewoners, en andere animatoren inspireren.

@component('mail::button', ['url' => route('fiches.show', [$fiche->initiative, $fiche])])
Bekijk je fiche
@endcomponent

[Alle diamantjes ontdekken →]({{ route('diamantjes.index') }})

Bedankt om dit te delen. Dit is precies waarom Hartverwarmers bestaat.

Warme groet,
Frederik & Maite van Hartverwarmers

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])
@endcomponent
