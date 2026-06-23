<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\ContributorAnniversaryNotification;
use App\Services\ContributorAnniversary\Payload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorAnniversaryNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_for_year_one(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);
        $payload = $this->emptyPayload();

        $mail = (new ContributorAnniversaryNotification($payload, year: 1))->toMail($user);

        $this->assertStringContainsString('Eén jaar', $mail->subject);
        $this->assertStringContainsString('Marleen', $mail->subject);
    }

    public function test_subject_for_year_three(): void
    {
        $user = User::factory()->create(['first_name' => 'Marleen']);
        $payload = $this->emptyPayload();

        $mail = (new ContributorAnniversaryNotification($payload, year: 3))->toMail($user);

        $this->assertStringContainsString('3 jaar', $mail->subject);
    }

    public function test_rendered_html_includes_first_fiche_title_and_theme(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload(title: 'Geurtjes-bingo', theme: 'samen koken');

        $html = (new ContributorAnniversaryNotification($payload, year: 5))->toMail($user)->render();

        $this->assertStringContainsString('Geurtjes-bingo', $html);
        $this->assertStringContainsString('Een idee over samen koken', $html);
    }

    public function test_rendered_html_includes_title_without_theme(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload(title: 'Geurtjes-bingo', theme: null);

        $html = (new ContributorAnniversaryNotification($payload, year: 5))->toMail($user)->render();

        $this->assertStringContainsString('Geurtjes-bingo', $html);
        $this->assertStringNotContainsString('Een idee over', $html);
    }

    public function test_rendered_html_omits_title_when_first_fiche_missing(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new ContributorAnniversaryNotification($payload, year: 5))->toMail($user)->render();

        $this->assertStringNotContainsString('Een idee over', $html);
        $this->assertStringContainsString('eerste fiche op Hartverwarmers', $html);
    }

    public function test_signoff_present(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new ContributorAnniversaryNotification($payload, year: 1))->toMail($user)->render();

        // HTML-renderer encodes &; assert against decoded HTML.
        $this->assertStringContainsString('Frederik & Maite van Hartverwarmers', html_entity_decode($html));
    }

    public function test_uses_kudos_unsubscribe_footer(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new ContributorAnniversaryNotification($payload, year: 1))->toMail($user)->render();

        $this->assertStringContainsString('type=kudos', $html);
    }

    public function test_primary_cta_carries_utm(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload(title: 'Geurtjes-bingo', theme: 'samen koken');

        $html = (new ContributorAnniversaryNotification($payload, year: 5))->toMail($user)->render();

        $this->assertStringContainsString('Deel je volgende fiche', $html);
        $this->assertStringContainsString('utm_campaign=anniversary', $html);
        $this->assertStringContainsString('utm_source=lifecycle', $html);
        $this->assertStringContainsString('utm_medium=email', $html);
        $this->assertStringContainsString('utm_content=primary', $html);
    }

    public function test_secondary_cta_links_to_initiative_with_utm(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload(title: 'Geurtjes-bingo', theme: 'samen koken', initiativeName: 'Samen koken', initiativeSlug: 'samen-koken');

        $html = (new ContributorAnniversaryNotification($payload, year: 5))->toMail($user)->render();

        $this->assertStringContainsString('andere uitwerkingen rond Samen koken', $html);
        $this->assertStringContainsString('initiatieven/samen-koken', $html);
        $this->assertStringContainsString('utm_content=secondary', $html);
    }

    public function test_secondary_cta_omitted_when_no_initiative(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload(title: 'Geurtjes-bingo', theme: 'samen koken', initiativeName: null, initiativeSlug: null);

        $html = (new ContributorAnniversaryNotification($payload, year: 5))->toMail($user)->render();

        $this->assertStringNotContainsString('andere uitwerkingen', $html);
        $this->assertStringNotContainsString('utm_content=secondary', $html);
    }

    private function payload(?string $title = null, ?string $theme = null, ?string $initiativeName = null, ?string $initiativeSlug = null): Payload
    {
        return new Payload(
            firstFicheTitle: $title,
            firstFicheTheme: $theme,
            firstFicheInitiativeName: $initiativeName,
            firstFicheInitiativeSlug: $initiativeSlug,
        );
    }

    private function emptyPayload(): Payload
    {
        return $this->payload();
    }
}
