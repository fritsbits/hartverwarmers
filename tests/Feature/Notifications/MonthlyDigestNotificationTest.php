<?php

namespace Tests\Feature\Notifications;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use App\Models\User;
use App\Notifications\MonthlyDigestNotification;
use App\Services\MonthlyDigest\Payload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MonthlyDigestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_is_static_inspiration_line(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $mail = (new MonthlyDigestNotification($payload))->toMail($user);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Verse ideeën voor de komende weken', $mail->subject);
    }

    public function test_uses_monthly_digest_view(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $mail = (new MonthlyDigestNotification($payload))->toMail($user);

        $this->assertSame('emails.monthly-digest', $mail->view);
    }

    public function test_view_data_includes_payload_and_notifiable(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $mail = (new MonthlyDigestNotification($payload))->toMail($user);

        $this->assertSame($payload, $mail->viewData['payload']);
        $this->assertSame($user->id, $mail->viewData['notifiable']->id);
    }

    public function test_rendered_html_contains_user_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Hoi Marleen', $html);
    }

    public function test_rendered_html_contains_logo_img(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('hartverwarmers-logo-email.png', $html);
    }

    public function test_rendered_html_uses_table_layout(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('role="presentation"', $html);
    }

    public function test_rendered_html_has_intro_text_even_when_payload_empty(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        // Defensive fallback: a non-greeting paragraph should be present.
        $this->assertStringContainsString('Hartverwarmers van de afgelopen periode', $html);
    }

    public function test_themes_section_renders_each_theme_title(): void
    {
        $theme1 = Theme::factory()->create(['title' => 'Moederdag']);
        $theme2 = Theme::factory()->create(['title' => 'Pinksteren']);

        $payload = new Payload(
            themes: new Collection([
                ThemeOccurrence::factory()->for($theme1)->create(['start_date' => '2026-05-14']),
                ThemeOccurrence::factory()->for($theme2)->create(['start_date' => '2026-05-28']),
            ]),
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 2,
            newFicheCount: 0,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Moederdag', $html);
        $this->assertStringContainsString('Pinksteren', $html);
        $this->assertStringContainsString("Thema's om alvast in te plannen", $html);
    }

    public function test_themes_section_hidden_when_zero_themes(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringNotContainsString("Thema's om alvast in te plannen", $html);
    }

    private function emptyPayload(): Payload
    {
        return new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 0,
            sentAt: now(),
        );
    }
}
