@component('mail::message')
Hoi {{ config('hartverwarmers.admin_first_name') }},

Op 1 {{ $rotation->month->locale('nl_BE')->isoFormat('MMMM') }} wordt automatisch een nieuw diamantje van de maand toegekend. Dit is de suggestie — je hoeft niets te doen als je akkoord bent:

@component('mail::panel')
**{{ $primary->title }}**
door {{ $primary->user->full_name }}@if ($primary->user->organisation) · {{ $primary->user->organisation }}@endif

@if (! is_null($primary->quality_score))Kwaliteitsscore {{ $primary->quality_score }} · @endif{{ $primary->likes_count }} kudos · {{ $primary->comments_count }} reacties · gedeeld op {{ $primary->created_at->locale('nl_BE')->isoFormat('D MMMM YYYY') }}

[Bekijk de fiche]({{ route('fiches.show', [$primary->initiative, $primary]) }})
@endcomponent

@if ($backups->isNotEmpty())
Liever een andere? Dit zijn de volgende kandidaten — één tik en een bevestiging volstaan:

@foreach ($backups as $backup)
**{{ $backup->title }}** — @if (! is_null($backup->quality_score))score {{ $backup->quality_score }} · @endif{{ $backup->likes_count }} kudos · {{ $backup->comments_count }} reacties
door {{ $backup->user->full_name }}@if ($backup->user->organisation) · {{ $backup->user->organisation }}@endif — [bekijk fiche]({{ route('fiches.show', [$backup->initiative, $backup]) }}) · [**Kies deze →**]({{ $chooseUrls[$backup->id] }})

@endforeach
@endif

De maker krijgt na de toekenning automatisch een felicitatiemailtje, en het nieuwe diamantje verschijnt vanaf dan in de maandelijkse updates.

Warme groet,
Hartverwarmers
@endcomponent
