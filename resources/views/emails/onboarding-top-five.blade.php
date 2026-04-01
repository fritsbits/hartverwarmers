@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Andere animatoren weten wat werkt. Dit zijn de 5 activiteiten die het meest bewaard worden op Hartverwarmers:

@foreach($topFiches as $index => $fiche)
**{{ $index + 1 }}. [{{ $fiche->title }}]({{ route('fiches.show', [$fiche->initiative, $fiche]) }})**

@endforeach

@component('mail::button', ['url' => url('/initiatieven')])
Bekijk alle activiteiten
@endcomponent

Wil je zelf iets delen? Het duurt maar een paar minuten en andere teams hebben er meteen iets aan.

@component('mail::button', ['url' => url('/fiches/nieuw'), 'color' => 'white'])
Deel je eerste activiteit
@endcomponent

Warme groet,
Het Hartverwarmers-team
@endcomponent
