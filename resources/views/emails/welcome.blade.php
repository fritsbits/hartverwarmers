@component('mail::message')
Hoi {{ $notifiable->first_name }}!

Welkom bij Hartverwarmers — fijn dat je erbij bent.

**Elke animator in een woonzorgcentrum bedenkt activiteiten voor bewoners. Alleen.** Terwijl een collega in Gent hetzelfde doet, en eentje in Leuven, en eentje in Brugge. Hartverwarmers bestaat omdat dat zonde is. Wat werkt, mag gedeeld worden — zodat niemand nog het warme water apart hoeft uit te vinden.

---

**Hoe werkt het?**

Op Hartverwarmers vind je **initiatieven** — activiteitenideeën georganiseerd rond zinvolle tijdsbesteding voor ouderen. Bij elk initiatief horen **fiches**: praktische uitwerkingen van echte animatoren, voor hun bewoners, in hun woonzorgcentrum.

Al {{ number_format($ficheCount) }} fiches gedeeld door animatoren uit heel Vlaanderen.

**Wat kan je doen?** Blader rustig door de initiatieven. Sla op wat je aanspreekt met de bladwijzer, zodat je het later makkelijk terugvindt. Geef een hartje als iets je raakt — bijdragers vinden het fijn te weten dat hun werk wordt gezien. En reageer gerust op een fiche als je er iets over kwijt wil.

@component('mail::button', ['url' => url('/initiatieven')])
Ontdek initiatieven
@endcomponent

De komende weken sturen we je de beste fiches en tips van onze community. Zodat je Hartverwarmers van alle kanten leert kennen.

Warme groet,
Het Hartverwarmers-team

@include('emails.partials.notification-footer', ['notifiable' => $notifiable, 'type' => 'onboarding'])
@endcomponent
