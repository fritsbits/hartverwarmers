@component('mail::message')
Hoi {{ $notifiable->first_name }},

Vandaag precies {{ $year === 1 ? 'één' : $year }} jaar geleden deelde je je eerste fiche op Hartverwarmers. **Bedankt om mee te bouwen aan deze community.**

Sindsdien:
- Heb je **{{ $payload->totalFiches }} fiches** gedeeld
- Werden ze samen **{{ $payload->totalBookmarks }} keer opgeslagen**
- Kregen ze **{{ $payload->totalComments }} reacties** van collega's

@if($payload->spotlightFiche)
Jouw meest geliefde fiche is **{{ $payload->spotlightFiche->title }}** — opgeslagen door {{ $payload->spotlightBookmarkCount }} collega's.

@component('mail::button', ['url' => route('fiches.show', [$payload->spotlightFiche->initiative, $payload->spotlightFiche])])
Bekijk je fiche
@endcomponent
@endif

We zijn blij dat je erbij bent. Op naar het volgende jaar.

Warme groet,
Frederik & Maite van Hartverwarmers

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])
@endcomponent
