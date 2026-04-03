@component('mail::message')
Hoi {{ $notifiable->first_name }}!

We hebben voor Hartverwarmers een kleine selectie fiches samengesteld die ons écht bijgebleven zijn — fiches die goed uitgewerkt zijn, die animatoren inspireren, en die het beste tonen wat de community te bieden heeft. Wij noemen ze onze **diamantjes**.

@foreach($fiches as $fiche)
@component('mail::panel')
**[{{ $fiche->title }}]({{ route('fiches.show', [$fiche->initiative, $fiche]) }})**

*Door {{ $fiche->user?->full_name ?? 'een animator' }}*

{{ str(strip_tags($fiche->description ?? ''))->limit(120) }}

[Bekijk deze fiche]({{ route('fiches.show', [$fiche->initiative, $fiche]) }})
@endcomponent

@endforeach

@component('mail::button', ['url' => url('/diamantjes')])
Bekijk alle diamantjes
@endcomponent

Warme groet,
Het Hartverwarmers-team
@endcomponent
