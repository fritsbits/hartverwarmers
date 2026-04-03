@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Je downloadde recent **{{ $fiche->title }}**. Hopelijk heb je er al iets mee gedaan met je bewoners — of staat het op je lijstje voor binnenkort.

Hoe was het? Twee kleine dingen die veel betekenen voor de begeleidster die deze fiche schreef:

@component('mail::button', ['url' => route('fiches.show', [$fiche->initiative, $fiche])])
Bekijk de fiche
@endcomponent

- **Bedank de auteur** met een hartje — dat doe je met de kudos-knop op de fiche.
- **Laat een reactie achter** als je tips hebt, iets anders aanpakte, of een vraag hebt. Dat maakt de fiche beter voor iedereen die hem daarna gebruikt.

Elke reactie, hoe kort ook, doet er toe.

Warme groet,
Het Hartverwarmers-team
@endcomponent
