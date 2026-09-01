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
use Illuminate\Support\Str;
use Tests\TestCase;

class MonthlyDigestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_features_the_theme_with_the_most_fiches(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Dierendag', '2026-08-04', fiches: 2),
            $this->occurrence('Internationale dag van de vriendschap', '2026-07-30', fiches: 8),
        ]);

        // An even cycle over a two-theme shortlist lands on the top-ranked slot,
        // so this asserts the ranking rather than the rotation.
        $mail = (new MonthlyDigestNotification($payload, cycle: 4))->toMail(User::factory()->create());

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame(
            'Internationale dag van de vriendschap komt eraan — 8 fiches liggen klaar',
            $mail->subject
        );
    }

    public function test_subject_prefers_a_theme_with_fiches_over_a_nearer_one_without(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Baarddag', '2026-09-02', fiches: 0),
            $this->occurrence('Dag van de senioren', '2026-09-28', fiches: 5),
        ]);

        $mail = (new MonthlyDigestNotification($payload, cycle: 4))->toMail(User::factory()->create());

        $this->assertSame('Dag van de senioren komt eraan — 5 fiches liggen klaar', $mail->subject);
    }

    public function test_subject_breaks_a_fiche_count_tie_on_the_nearest_date(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Wereld hartdag', '2026-09-29', fiches: 4),
            $this->occurrence('Dag van de koffie', '2026-09-05', fiches: 4),
        ]);

        $mail = (new MonthlyDigestNotification($payload, cycle: 4))->toMail(User::factory()->create());

        $this->assertSame('Dag van de koffie komt eraan — 4 fiches liggen klaar', $mail->subject);
    }

    public function test_subject_uses_singular_wording_for_a_theme_with_one_fiche(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Pinksteren', '2026-05-24', fiches: 1),
        ]);

        $mail = (new MonthlyDigestNotification($payload, cycle: 3))->toMail(User::factory()->create());

        $this->assertSame('Pinksteren komt eraan — 1 fiche ligt klaar', $mail->subject);
    }

    public function test_subject_invites_instead_of_promising_when_the_theme_has_no_fiches(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Alzheimerdag', '2026-09-21', fiches: 0),
        ]);

        $mail = (new MonthlyDigestNotification($payload, cycle: 3))->toMail(User::factory()->create());

        // The digest still leads with the concrete hook, but it never promises
        // fiches that do not exist.
        $this->assertSame('Alzheimerdag komt eraan — heb jij al een idee?', $mail->subject);
    }

    public function test_consecutive_cycles_receive_different_subject_lines(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Dag van de fotografie', '2026-08-19', fiches: 8),
            $this->occurrence('Internationale kattendag', '2026-08-08', fiches: 7),
            $this->occurrence('Dag van de jeugd', '2026-08-12', fiches: 6),
        ]);

        $user = User::factory()->create();

        $subjects = collect([3, 4, 5])
            ->map(fn (int $cycle): string => (new MonthlyDigestNotification($payload, cycle: $cycle))->toMail($user)->subject)
            ->all();

        $this->assertCount(3, array_unique($subjects), 'Three consecutive cycles should each get their own line.');
    }

    public function test_rotation_wraps_around_when_fewer_themes_than_shortlist_slots(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Moederdag', '2026-05-10', fiches: 2),
            $this->occurrence('Vaderdag', '2026-06-14', fiches: 3),
        ]);

        $user = User::factory()->create();
        $subjectFor = fn (int $cycle): string => (new MonthlyDigestNotification($payload, cycle: $cycle))->toMail($user)->subject;

        $this->assertSame($subjectFor(4), $subjectFor(6));
        $this->assertNotSame($subjectFor(4), $subjectFor(5));
    }

    public function test_shortlist_never_reaches_the_weakest_candidates(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Dag van de fotografie', '2026-08-19', fiches: 8),
            $this->occurrence('Internationale kattendag', '2026-08-08', fiches: 7),
            $this->occurrence('Dag van de jeugd', '2026-08-12', fiches: 6),
            $this->occurrence('Wereldemojidag', '2026-07-17', fiches: 0),
            $this->occurrence('Baarddag', '2026-09-02', fiches: 0),
        ]);

        $user = User::factory()->create();

        $subjects = collect(range(0, 11))
            ->map(fn (int $cycle): string => (new MonthlyDigestNotification($payload, cycle: $cycle))->toMail($user)->subject)
            ->unique();

        $this->assertCount(3, $subjects, 'Only the top three candidates may headline.');
        $this->assertEmpty(
            $subjects->filter(fn (string $s): bool => str_contains($s, 'Wereldemojidag') || str_contains($s, 'Baarddag')),
            'A theme without fiches must never headline while richer ones are available.'
        );
    }

    public function test_subject_falls_back_to_diamond_when_no_themes(): void
    {
        $diamond = Fiche::factory()->published()->create([
            'title' => 'Geurtjes-bingo voor mensen met dementie',
            'has_diamond' => true,
        ]);

        $payload = new Payload(
            themes: new Collection,
            diamond: $diamond->fresh(['user', 'initiative']),
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 3,
            sentAt: now(),
        );

        $mail = (new MonthlyDigestNotification($payload))->toMail(User::factory()->create());

        $this->assertSame('Fiche van de maand: Geurtjes-bingo voor mensen met dementie', $mail->subject);
    }

    public function test_subject_falls_back_to_fiche_count_when_no_themes_or_diamond(): void
    {
        $payload = new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 9,
            sentAt: now(),
        );

        $mail = (new MonthlyDigestNotification($payload))->toMail(User::factory()->create());

        $this->assertSame('9 nieuwe fiches uit andere woonzorgcentra', $mail->subject);
    }

    public function test_subject_uses_singular_fiche_wording_for_one_fiche(): void
    {
        $payload = new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 1,
            sentAt: now(),
        );

        $mail = (new MonthlyDigestNotification($payload))->toMail(User::factory()->create());

        $this->assertSame('1 nieuwe fiche uit een ander woonzorgcentrum', $mail->subject);
    }

    public function test_subject_uses_evergreen_line_when_payload_empty(): void
    {
        $mail = (new MonthlyDigestNotification($this->emptyPayload()))->toMail(User::factory()->create());

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

    public function test_rendered_html_contains_feedback_invite(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Mis je iets, of heb je een idee?', $html);
        // The feedback CTA must be a tracked newsletter click link (like the footer share CTA),
        // so an empty-payload digest now has at least 2 tracked click URLs: footer share + feedback.
        $count = substr_count($html, '/n/'.$user->id.'/click');
        $this->assertGreaterThanOrEqual(2, $count, "Expected the feedback invite to add a tracked click URL; found {$count}.");
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

    public function test_footer_links_to_manage_and_unsubscribe(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Meldingen beheren', $html);
        $this->assertStringContainsString(route('profile.notifications'), $html);
        $this->assertStringContainsString('Uitschrijven', $html);
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

    public function test_theme_links_target_each_occurrences_own_month(): void
    {
        $juneTheme = Theme::factory()->create(['title' => 'Dag van de mantelzorger', 'slug' => 'dag-van-de-mantelzorger']);
        $julyTheme = Theme::factory()->create(['title' => 'Vlaamse feestdag', 'slug' => 'vlaamse-feestdag']);

        $payload = new Payload(
            themes: new Collection([
                ThemeOccurrence::factory()->for($juneTheme)->create(['start_date' => '2026-06-23']),
                ThemeOccurrence::factory()->for($julyTheme)->create(['start_date' => '2026-07-11']),
            ]),
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 2,
            newFicheCount: 0,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        preg_match_all('/[?&]to=([A-Za-z0-9%]+)/', $html, $matches);
        $destinations = array_map(
            fn (string $encoded): string => base64_decode(urldecode($encoded), true) ?: '',
            $matches[1],
        );

        $juneLink = $this->firstDestinationContaining($destinations, '#thema-dag-van-de-mantelzorger');
        $this->assertNotNull($juneLink, 'expected a tracked link to the mantelzorger anchor');
        $this->assertStringContainsString('maand=2026-06', $juneLink);

        $julyLink = $this->firstDestinationContaining($destinations, '#thema-vlaamse-feestdag');
        $this->assertNotNull($julyLink, 'expected a tracked link to the vlaamse-feestdag anchor');
        $this->assertStringContainsString('maand=2026-07', $julyLink);
    }

    /**
     * @param  array<int, string>  $destinations
     */
    private function firstDestinationContaining(array $destinations, string $needle): ?string
    {
        foreach ($destinations as $destination) {
            if (str_contains($destination, $needle)) {
                return $destination;
            }
        }

        return null;
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

    public function test_rendered_html_includes_team_signoff(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('Warme groet', $html);
        $this->assertStringContainsString('Het Hartverwarmers-team', $html);
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

    public function test_primary_content_links_use_tracked_click_urls(): void
    {
        $theme = Theme::factory()->create(['title' => 'Moederdag']);
        $occurrence = ThemeOccurrence::factory()->for($theme)->create();

        $diamond = Fiche::factory()->published()->create(['has_diamond' => true]);
        $recentFiche = Fiche::factory()->published()->create();

        $payload = new Payload(
            themes: new Collection([$occurrence]),
            diamond: $diamond->fresh(['user', 'initiative']),
            recentFiches: (new Collection([$recentFiche]))->load(['user', 'initiative']),
            upcomingThemeCount: 1,
            newFicheCount: 1,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        // 4 wrap sites: theme link, diamond fiche, recent fiche tile, footer share CTA.
        // Count the click-redirect anchors to confirm every primary link was wrapped.
        $count = substr_count($html, '/n/'.$user->id.'/click');
        $this->assertGreaterThanOrEqual(4, $count, "Expected 4+ tracked click URLs (one per primary link site), found {$count}.");
    }

    public function test_tracked_destinations_carry_monthly_digest_utm(): void
    {
        $theme = Theme::factory()->create(['title' => 'Moederdag']);
        $occurrence = ThemeOccurrence::factory()->for($theme)->create();

        $diamond = Fiche::factory()->published()->create(['has_diamond' => true]);
        $recentFiche = Fiche::factory()->published()->create();

        $payload = new Payload(
            themes: new Collection([$occurrence]),
            diamond: $diamond->fresh(['user', 'initiative']),
            recentFiches: (new Collection([$recentFiche]))->load(['user', 'initiative']),
            upcomingThemeCount: 1,
            newFicheCount: 1,
            sentAt: now(),
        );

        $user = User::factory()->create();
        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        preg_match_all('/[?&]to=([A-Za-z0-9%]+)/', $html, $matches);
        $this->assertNotEmpty($matches[1], 'expected tracked links with a to= param');

        $decodedDestinations = array_map(
            fn (string $encoded): string => base64_decode(urldecode($encoded), true) ?: '',
            $matches[1],
        );

        $taggedDestinations = array_filter(
            $decodedDestinations,
            fn (string $url): bool => str_contains($url, 'utm_medium=email'),
        );

        $this->assertNotEmpty($taggedDestinations, 'expected at least one UTM-tagged destination');

        foreach ($taggedDestinations as $url) {
            $this->assertStringContainsString('utm_source=newsletter', $url);
            $this->assertStringContainsString('utm_campaign=monthly-digest', $url);
        }
    }

    public function test_unsubscribe_and_manage_links_are_not_tracked(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new MonthlyDigestNotification($payload))->toMail($user)->render();

        $this->assertStringContainsString('/nieuwsbrief/uitschrijven/'.$user->id, $html);
        $this->assertStringContainsString(route('profile.notifications'), $html);
    }

    public function test_preview_text_lists_theme_names_excluding_the_subject_headline(): void
    {
        // 'Vaderdag' carries the fiches, so it headlines the SUBJECT and is
        // dropped from the preheader — the two lines never echo each other.
        $payload = $this->themePayload([
            $this->occurrence('Vaderdag', '2026-06-14', fiches: 7),
            $this->occurrence('Internationale kattendag', '2026-08-08'),
            $this->occurrence('Dag van de senioren', '2026-08-13'),
            $this->occurrence('Wereld Chocoladedag', '2026-07-07'),
            $this->occurrence('Dag van de fotografie', '2026-08-19'),
            $this->occurrence('Nationale Frietjesdag', '2026-07-13'),
        ]);

        $user = User::factory()->create();
        $mail = (new MonthlyDigestNotification($payload, cycle: 3))->toMail($user);

        $this->assertSame('Vaderdag komt eraan — 7 fiches liggen klaar', $mail->subject);
        $this->assertSame(
            "Internationale kattendag, Dag van de senioren, Wereld Chocoladedag en 2 andere thema's — plus 6 nieuwe fiches van collega's.",
            $mail->metadata['preview_text'] ?? null
        );
    }

    public function test_preview_text_uses_singular_wording_for_one_remaining_theme(): void
    {
        // Five upcoming themes: 'Vaderdag' headlines the subject, three others
        // are named in the preheader, leaving exactly one — which must read
        // '1 ander thema', not '1 andere thema's'.
        $payload = $this->themePayload([
            $this->occurrence('Vaderdag', '2026-06-14', fiches: 7),
            $this->occurrence('Internationale kattendag', '2026-08-08'),
            $this->occurrence('Dag van de senioren', '2026-08-13'),
            $this->occurrence('Wereld Chocoladedag', '2026-07-07'),
            $this->occurrence('Dag van de fotografie', '2026-08-19'),
        ]);

        $mail = (new MonthlyDigestNotification($payload, cycle: 3))->toMail(User::factory()->create());

        $this->assertSame(
            "Internationale kattendag, Dag van de senioren, Wereld Chocoladedag en 1 ander thema — plus 6 nieuwe fiches van collega's.",
            $mail->metadata['preview_text'] ?? null
        );
    }

    public function test_preview_text_falls_back_to_fiches_when_the_only_theme_is_featured(): void
    {
        $payload = $this->themePayload([
            $this->occurrence('Moederdag', '2026-05-10', fiches: 2),
        ]);

        $mail = (new MonthlyDigestNotification($payload))->toMail(User::factory()->create());

        // The lone theme headlines the subject, so the preheader has no theme
        // left to list and pivots to the fiche count.
        $this->assertSame('Moederdag komt eraan — 2 fiches liggen klaar', $mail->subject);
        $this->assertSame(
            '6 nieuwe fiches uit andere woonzorgcentra om uit te putten.',
            $mail->metadata['preview_text'] ?? null
        );
    }

    public function test_preview_text_names_the_single_remaining_theme(): void
    {
        // Two upcoming themes: 'Dierendag' carries the fiches and headlines the
        // subject, the other one carries the preheader.
        $payload = $this->themePayload([
            $this->occurrence('Internationale kattendag', '2026-08-08'),
            $this->occurrence('Dierendag', '2026-10-04', fiches: 3),
        ]);

        $mail = (new MonthlyDigestNotification($payload, cycle: 4))->toMail(User::factory()->create());

        $this->assertSame('Dierendag komt eraan — 3 fiches liggen klaar', $mail->subject);
        $this->assertSame(
            "Internationale kattendag en 6 nieuwe fiches van collega's.",
            $mail->metadata['preview_text'] ?? null
        );
    }

    public function test_preview_text_when_zero_themes(): void
    {
        $payload = new Payload(
            themes: new Collection,
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: 0,
            newFicheCount: 6,
            sentAt: now(),
        );

        $mail = (new MonthlyDigestNotification($payload))->toMail(User::factory()->create());

        $this->assertSame(
            '6 nieuwe fiches uit andere woonzorgcentra om uit te putten.',
            $mail->metadata['preview_text'] ?? null
        );
    }

    /**
     * Build an occurrence hydrated the way Composer hydrates it, so the
     * published fiche count the subject line reads is a real query result.
     */
    private function occurrence(string $title, string $startDate, int $fiches = 0): ThemeOccurrence
    {
        $theme = Theme::factory()->create(['title' => $title, 'slug' => Str::slug($title)]);

        if ($fiches > 0) {
            $theme->fiches()->attach(Fiche::factory()->published()->count($fiches)->create());
        }

        return ThemeOccurrence::factory()
            ->for($theme)
            ->create(['start_date' => $startDate])
            ->load(['theme' => fn ($q) => $q->withCount(['fiches' => fn ($q) => $q->published()])]);
    }

    /**
     * @param  array<int, ThemeOccurrence>  $occurrences
     */
    private function themePayload(array $occurrences, int $newFicheCount = 6): Payload
    {
        return new Payload(
            themes: new Collection($occurrences),
            diamond: null,
            recentFiches: new Collection,
            upcomingThemeCount: count($occurrences),
            newFicheCount: $newFicheCount,
            sentAt: now(),
        );
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
