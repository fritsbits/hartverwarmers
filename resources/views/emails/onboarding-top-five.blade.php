@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Andere animatoren weten wat werkt. Hier zijn twee lijstjes: wat er nu populair is, en wat al jarenlang gedeeld wordt.

@if($recentFiches->isNotEmpty())
**Trending deze maand**

@foreach($recentFiches as $index => $fiche)
**{{ $index + 1 }}. [{{ $fiche->title }}]({{ route('fiches.show', [$fiche->initiative, $fiche]) }})**

@endforeach
@endif

@if($allTimeFiches->isNotEmpty())
**Tijdloze favorieten**

@foreach($allTimeFiches as $index => $fiche)
**{{ $index + 1 }}. [{{ $fiche->title }}]({{ route('fiches.show', [$fiche->initiative, $fiche]) }})**

@endforeach
@endif

@component('mail::button', ['url' => url('/initiatieven')])
Bekijk alle initiatieven
@endcomponent

Wil je zelf iets delen? Het duurt maar een paar minuten en andere teams hebben er meteen iets aan.

@component('mail::button', ['url' => url('/fiches/nieuw'), 'color' => 'white'])
Deel je eerste fiche
@endcomponent

Warme groet,
Het Hartverwarmers-team
@endcomponent
