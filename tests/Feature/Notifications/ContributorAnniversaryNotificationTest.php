<?php

namespace Tests\Feature\Notifications;

use App\Models\Fiche;
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

    public function test_rendered_html_includes_all_three_totals(): void
    {
        $user = User::factory()->create();
        $payload = new Payload(
            totalFiches: 4,
            totalBookmarks: 27,
            totalComments: 6,
            spotlightFiche: null,
            spotlightBookmarkCount: null,
        );

        $html = (new ContributorAnniversaryNotification($payload, year: 1))->toMail($user)->render();

        $this->assertStringContainsString('4 fiches', $html);
        $this->assertStringContainsString('27', $html);
        $this->assertStringContainsString('6 reacties', $html);
    }

    public function test_rendered_html_includes_spotlight_when_payload_has_one(): void
    {
        $user = User::factory()->create();
        $spotlight = Fiche::factory()->create(['title' => 'Geurtjes-bingo']);
        $payload = new Payload(
            totalFiches: 4,
            totalBookmarks: 27,
            totalComments: 6,
            spotlightFiche: $spotlight->load('initiative'),
            spotlightBookmarkCount: 15,
        );

        $html = (new ContributorAnniversaryNotification($payload, year: 1))->toMail($user)->render();

        $this->assertStringContainsString('Geurtjes-bingo', $html);
        $this->assertStringContainsString('15', $html);
    }

    public function test_rendered_html_omits_spotlight_when_payload_has_none(): void
    {
        $user = User::factory()->create();
        $payload = $this->emptyPayload();

        $html = (new ContributorAnniversaryNotification($payload, year: 1))->toMail($user)->render();

        $this->assertStringNotContainsString('Jouw meest geliefde fiche', $html);
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

    private function emptyPayload(): Payload
    {
        return new Payload(
            totalFiches: 0,
            totalBookmarks: 0,
            totalComments: 0,
            spotlightFiche: null,
            spotlightBookmarkCount: null,
        );
    }
}
