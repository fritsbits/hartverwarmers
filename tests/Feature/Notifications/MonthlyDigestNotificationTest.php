<?php

namespace Tests\Feature\Notifications;

use App\Models\Fiche;
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

    public function test_diamond_section_renders_title_author_and_excerpt(): void
    {
        $author = User::factory()->create([
            'first_name' => 'Marleen',
            'last_name' => 'Geertsen',
            'organisation' => 'WZC Avondrust',
        ]);
        $diamond = Fiche::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Geurtjes-bingo voor mensen met dementie',
            'description' => str_repeat('Met flesjes essentiële olie. ', 30),
            'has_diamond' => true,
        ]);

        $payload = new Payload(
            themes: new Collection,
            diamond: $diamond->fresh(['user', 'initiative']),
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 0,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Diamantje van de maand', $html);
        $this->assertStringContainsString('Geurtjes-bingo voor mensen met dementie', $html);
        $this->assertStringContainsString('Marleen Geertsen', $html);
        $this->assertStringContainsString('WZC Avondrust', $html);
        $this->assertStringContainsString('Lees de fiche', $html);
    }

    public function test_diamond_section_hidden_when_null(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringNotContainsString('Diamantje van de maand', $html);
    }

    public function test_recent_fiches_section_renders_each_fiche(): void
    {
        $fiches = new Collection;
        foreach (['Boekenruil-namiddag', 'Stoelendans light', 'Vroeger-en-nu fotoquiz'] as $title) {
            $fiches->push(Fiche::factory()->published()->create(['title' => $title]));
        }

        $payload = new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: $fiches->load(['user', 'initiative']),
            upcomingThemeCount: 0,
            newFicheCount: 3,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Recent gedeeld', $html);
        $this->assertStringContainsString('Fiches uit andere woonzorgcentra', $html);
        $this->assertStringContainsString('Pak wat past, pas aan, deel terug.', $html);
        $this->assertStringContainsString('Boekenruil-namiddag', $html);
        $this->assertStringContainsString('Stoelendans light', $html);
        $this->assertStringContainsString('Vroeger-en-nu fotoquiz', $html);
    }

    public function test_recent_fiches_section_hidden_when_empty(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringNotContainsString('Recent gedeeld', $html);
    }

    public function test_recent_fiches_meta_has_no_trailing_middot_when_organisation_empty(): void
    {
        $author = User::factory()->create(['first_name' => 'Lena', 'organisation' => '']);
        $fiche = Fiche::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Stoelendans light',
        ]);

        $payload = new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: (new Collection([$fiche]))->load(['user', 'initiative']),
            upcomingThemeCount: 0,
            newFicheCount: 1,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringNotContainsString('Lena · ', $html);
        $this->assertStringContainsString('Lena', $html);
    }

    public function test_diamond_meta_has_no_trailing_middot_when_organisation_empty(): void
    {
        $author = User::factory()->create(['first_name' => 'Marleen', 'last_name' => 'Geertsen', 'organisation' => '']);
        $diamond = Fiche::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Geurtjes-bingo',
            'has_diamond' => true,
        ]);

        $payload = new Payload(
            themes: new Collection,
            diamond: $diamond->fresh(['user', 'initiative']),
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 0,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringNotContainsString('Marleen Geertsen · ', $html);
        $this->assertStringContainsString('Marleen Geertsen', $html);
    }

    public function test_footer_contains_signed_unsubscribe_link(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Uitschrijven', $html);
        $this->assertMatchesRegularExpression(
            '#/nieuwsbrief/uitschrijven/\d+\?[^"]*signature=[a-f0-9]+#',
            $html,
            'Footer should contain a Laravel signed-URL unsubscribe link'
        );
    }

    public function test_footer_contains_postal_address(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Impact Studio, Kasteeldreef 47, 1083 Ganshoren', $html);
    }

    public function test_footer_contains_contribution_cta(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Deel jouw fiche', $html);
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
