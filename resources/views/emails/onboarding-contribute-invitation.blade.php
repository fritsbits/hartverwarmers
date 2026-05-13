@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Je downloadde al **{{ $downloadCount }} activiteiten** van andere animatoren. Hopelijk heb je er al iets moois mee gedaan met je bewoners.

Nu een vraag: heb jij activiteiten die goed werken bij jouw bewoners? Dingen die je hier nog niet zag? Een idee dat anderen ook zouden kunnen gebruiken?

Het hoeft niet perfect te zijn. Een paar zinnen over wat je doet, voor wie het werkt, en hoe je het aanpakt. Dat is genoeg.

@component('mail::button', ['url' => url('/fiches/nieuw')])
Deel je eerste activiteit
@endcomponent

Andere animatoren in Vlaanderen zullen je er dankbaar voor zijn.

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'onboarding'])

Warme groet,
Het Hartverwarmers-team
@endcomponent
