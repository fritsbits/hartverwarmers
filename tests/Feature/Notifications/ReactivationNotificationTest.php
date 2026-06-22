<?php

namespace Tests\Feature\Notifications;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use App\Models\User;
use App\Notifications\ReactivationNotification;
use App\Support\Reactivation\ReactivationContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReactivationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function render(User $user): string
    {
        $content = new ReactivationContent(412, 230, new Collection);

        return (string) (new ReactivationNotification($content))
            ->toMail($user)->render();
    }

    public function test_subject_leads_with_the_live_activity_count(): void
    {
        $user = User::factory()->create();
        $mail = (new ReactivationNotification(new ReactivationContent(411, 1, new Collection)))
            ->toMail($user);

        $this->assertSame("411 activiteiten van collega's, klaar voor je bewoners", $mail->subject);
    }

    public function test_renders_live_activity_count(): void
    {
        $html = $this->render(User::factory()->create());

        $this->assertStringContainsString('412', $html);
    }

    public function test_cta_links_to_theme_calendar_with_campaign_utm(): void
    {
        $user = User::factory()->create();
        $html = $this->render($user);

        // The tracked redirect base64-encodes the destination into the `to=` param.
        // Extract the `to=` param and decode it to assert the campaign tag.
        preg_match('/[?&]to=([A-Za-z0-9%]+)/', $html, $matches);
        $this->assertNotEmpty($matches[1], 'expected a tracked link with a to= param');

        $destination = base64_decode(urldecode($matches[1]), true) ?: '';

        $this->assertStringContainsString('utm_campaign=reactivatie-2026-06', $destination);
        $this->assertStringContainsString('utm_source=newsletter', $destination);
        $this->assertStringContainsString('utm_content=themakalender', $destination);
        $this->assertStringContainsString(route('themes.index'), $destination);

        // The raw HTML must still contain the click-redirect path (newsletter.click route).
        $this->assertStringContainsString('/n/'.$user->id.'/click', $html);
    }

    public function test_each_theme_row_is_a_tracked_link_to_its_theme(): void
    {
        $user = User::factory()->create();
        $theme = Theme::factory()->create(['title' => 'Muziek en herinnering']);
        $occurrence = ThemeOccurrence::factory()->for($theme)->create([
            'start_date' => now()->addDays(5)->toDateString(),
        ]);

        $content = new ReactivationContent(100, 10, new Collection([$occurrence]));
        $html = (string) (new ReactivationNotification($content))->toMail($user)->render();

        // Every tracked link base64-encodes its destination into the `to=` param.
        preg_match_all('/[?&]to=([A-Za-z0-9%]+)/', $html, $matches);
        $destinations = array_map(fn ($enc): string => base64_decode(urldecode($enc), true) ?: '', $matches[1]);

        $themeLink = collect($destinations)->first(fn (string $d): bool => str_contains($d, '#thema-'.$theme->slug));

        $this->assertNotNull($themeLink, 'each theme row should be a tracked link to the theme anchor');
        $this->assertStringContainsString('utm_campaign=reactivatie-2026-06', $themeLink);
        $this->assertStringContainsString('utm_source=newsletter', $themeLink);
        $this->assertStringContainsString('utm_content=thema', $themeLink);
    }

    public function test_unsubscribe_link_is_not_utm_tagged(): void
    {
        $user = User::factory()->create();
        $html = $this->render($user);

        // The unsubscribe anchor must point at the real signed route.
        $this->assertStringContainsString('/nieuwsbrief/uitschrijven/'.$user->id, $html);
        $this->assertStringNotContainsString('unsubscribe?utm', $html);
    }
}
