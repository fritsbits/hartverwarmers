@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Je fiches werden al **{{ $bookmarkCount }} keer** bewaard door andere animatoren. Ze gebruiken jouw werk om het leven van bewoners te verrijken. Dankjewel.

@if($sparseInitiatives->isNotEmpty())
**Hier is nog ruimte voor jouw kennis**

Er zijn initiatieven op Hartverwarmers die nog maar weinig fiches hebben. Misschien heb jij daar iets voor in de la?

@foreach($sparseInitiatives as $initiative)
- [{{ $initiative->title }}]({{ route('initiatives.show', $initiative) }}) — {{ $initiative->published_fiches_count }} {{ $initiative->published_fiches_count === 1 ? 'fiche' : 'fiches' }}
@endforeach

@endif

@component('mail::button', ['url' => url('/fiches/nieuw')])
Deel nog een fiche
@endcomponent

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])

Warme groet,
Het Hartverwarmers-team
@endcomponent
