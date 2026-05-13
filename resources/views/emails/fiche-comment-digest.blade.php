@component('mail::message')
Hoi {{ $user->first_name }}!

@if(count($commentPayloads) === 1)
Er is een nieuwe reactie op je fiche **{{ $fiche->title }}**.
@else
Er zijn {{ count($commentPayloads) }} nieuwe reacties op je fiche **{{ $fiche->title }}**.
@endif

@foreach($commentPayloads as $comment)
@component('mail::panel')
**{{ $comment['commenter_name'] }}** schreef:

{{ $comment['body_excerpt'] }}

[Bekijk reactie]({{ $comment['comment_url'] }})
@endcomponent

@endforeach

@component('mail::button', ['url' => $ficheUrl])
Bekijk alle reacties
@endcomponent

@include('emails.partials.notification-footer', ['notifiable' => $user, 'type' => 'comments'])

Warme groet,
Het Hartverwarmers-team
@endcomponent
