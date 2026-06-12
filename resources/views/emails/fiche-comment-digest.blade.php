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

[Bekijk reactie]({{ \App\Support\EmailLink::to($comment['comment_url'], 'comment-digest', 'transactional', 'comment') }})
@endcomponent

@endforeach

@component('mail::button', ['url' => \App\Support\EmailLink::to($ficheUrl, 'comment-digest', 'transactional', 'fiche')])
Bekijk alle reacties
@endcomponent

Warme groet,
Het Hartverwarmers-team

@include('emails.partials.notification-footer', ['notifiable' => $user, 'type' => 'comments'])
@endcomponent
