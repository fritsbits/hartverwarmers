<?php

namespace Tests\Feature\Notifications;

use App\Models\Fiche;
use App\Models\User;
use App\Notifications\FicheDiamondAwardedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class FicheDiamondAwardedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_contains_fiche_title_and_diamantje_word(): void
    {
        $fiche = Fiche::factory()->create(['title' => 'Geurtjes-bingo']);
        $user = User::factory()->create(['first_name' => 'Marleen']);

        $mail = (new FicheDiamondAwardedNotification($fiche))->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertStringContainsString('Geurtjes-bingo', $mail->subject);
        $this->assertStringContainsString('diamantje', $mail->subject);
    }

    public function test_rendered_html_contains_fiche_title_and_signoff(): void
    {
        $fiche = Fiche::factory()->create(['title' => 'Geurtjes-bingo']);
        $user = User::factory()->create(['first_name' => 'Marleen']);

        $html = html_entity_decode((new FicheDiamondAwardedNotification($fiche))->toMail($user)->render());

        $this->assertStringContainsString('Geurtjes-bingo', $html);
        $this->assertStringContainsString('Frederik & Maite van Hartverwarmers', $html);
    }

    public function test_rendered_html_uses_kudos_unsubscribe_footer(): void
    {
        $fiche = Fiche::factory()->create();
        $user = User::factory()->create();

        $html = (new FicheDiamondAwardedNotification($fiche))->toMail($user)->render();

        // Kudos-type footer goes to the kudos unsubscribe URL
        $this->assertMatchesRegularExpression('#type=kudos#', $html);
    }
}
