@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Je fiches werden al **{{ $bookmarkCount }} keer** bewaard door andere animatoren. Ze gebruiken jouw werk om het leven van bewoners te verrijken. Dankjewel.

@if($sparseInitiatives->isNotEmpty())
**Hier is nog ruimte voor jouw kennis**

Er zijn initiatieven op Hartverwarmers die nog maar weinig fiches hebben. Misschien heb jij daar iets voor in de la?

@foreach($sparseInitiatives as $initiative)
- [{{ $initiative->title }}]({{ \App\Support\EmailLink::to(route('initiatives.show', $initiative), 'onboarding-10-bookmarks', 'lifecycle', 'initiative') }}) — {{ $initiative->published_fiches_count }} {{ $initiative->published_fiches_count === 1 ? 'fiche' : 'fiches' }}
@endforeach

@endif

@component('mail::button', ['url' => \App\Support\EmailLink::to(url('/fiches/nieuw'), 'onboarding-10-bookmarks', 'lifecycle', 'create-fiche')])
Deel nog een fiche
@endcomponent

Warme groet,
Het Hartverwarmers-team

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'kudos'])
@endcomponent
