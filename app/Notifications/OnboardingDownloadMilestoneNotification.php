<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class OnboardingDownloadMilestoneNotification extends BaseMailNotification
{
    public function __construct(public int $downloadCount) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Je downloadde al {$this->downloadCount} activiteiten — tijd om iets terug te geven?")
            ->greeting("Hoi {$notifiable->first_name}!")
            ->line("Je downloadde al **{$this->downloadCount} activiteiten** van andere animatoren. Hopelijk heb je er al iets moois mee gedaan met je bewoners.")
            ->line('Nu een vraag: heb jij activiteiten die goed werken bij jouw bewoners? Dingen die je hier nog niet zag? Een idee dat anderen ook zouden kunnen gebruiken?')
            ->line('Het hoeft niet perfect te zijn. Een paar zinnen over wat je doet, voor wie het werkt, en hoe je het aanpakt. Dat is genoeg.')
            ->action('Deel je eerste activiteit', url('/fiches/nieuw'))
            ->line('Andere animatoren in Vlaanderen zullen je er dankbaar voor zijn.')
            ->salutation("Warme groet,\nHet Hartverwarmers-team");
    }
}
