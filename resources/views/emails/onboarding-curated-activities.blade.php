@component('mail::message')
Hoi {{ $notifiable->first_name }}!

We dachten dat deze activiteiten iets voor jou konden zijn. Andere animatoren gebruiken ze al in hun woonzorgcentrum.

@foreach($fiches as $fiche)
@component('mail::panel')
**[{{ $fiche->title }}]({{ route('fiches.show', [$fiche->initiative, $fiche]) }})**
*Door {{ $fiche->user->name ?? 'een animator' }}*
{{ str(strip_tags($fiche->description ?? ''))->limit(120) }}

[Bekijk deze activiteit]({{ route('fiches.show', [$fiche->initiative, $fiche]) }})
@endcomponent

@endforeach

Warme groet,
Het Hartverwarmers-team
@endcomponent
