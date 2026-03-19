@component('mail::message')
Hoi {{ $notifiable->first_name }}!

{{ $commenter->full_name }} heeft een reactie geplaatst op je fiche **{{ $fiche->title }}**:

@component('mail::panel')
{{ $comment->body }}
@endcomponent

@component('mail::button', ['url' => $url])
Beantwoord de reactie
@endcomponent

Warme groet,
Het Hartverwarmers-team
@endcomponent
