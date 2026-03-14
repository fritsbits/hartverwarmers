<?php

namespace Tests\Feature;

use App\Livewire\SupportContactForm;
use App\Mail\SupportMessage;
use App\Models\Fiche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_returns_ok(): void
    {
        $response = $this->get('/over-ons');

        $response->assertOk();
        $response->assertSeeText('Jij hoort niet achter een computer te zitten.');
        $response->assertSee('Deel Hartverwarmers');
    }

    public function test_support_message_mailable_has_correct_envelope(): void
    {
        $mailable = new SupportMessage(
            senderName: 'Jan Janssen',
            senderEmail: 'jan@example.com',
            senderMessage: 'Ik wil graag bijdragen.',
        );

        $mailable->assertHasSubject('Steunbericht via Hartverwarmers — Jan Janssen');
        $mailable->assertTo(config('mail.support_address'));
        $mailable->assertHasReplyTo('jan@example.com');
    }

    public function test_about_page_shows_dynamic_stats(): void
    {
        $user = User::factory()->create(['organisation' => 'WZC Test']);
        Fiche::factory()->for($user)->published()->create();

        $response = $this->get('/over-ons');

        $response->assertOk();
        $response->assertViewHas('aboutStats');
        $data = $response->viewData('aboutStats');
        $this->assertArrayHasKey('fiches_count', $data);
        $this->assertArrayHasKey('contributors_count', $data);
        $this->assertArrayHasKey('users_count', $data);
        $this->assertGreaterThan(0, $data['fiches_count']);
    }

    public function test_support_form_validates_required_fields(): void
    {
        Livewire::test(SupportContactForm::class)
            ->call('send')
            ->assertHasErrors(['name' => 'required', 'email' => 'required', 'message' => 'required']);
    }

    public function test_support_form_validates_email_format(): void
    {
        Livewire::test(SupportContactForm::class)
            ->set('name', 'Jan')
            ->set('email', 'not-an-email')
            ->set('message', 'Test bericht')
            ->call('send')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_support_form_sends_email(): void
    {
        Mail::fake();

        Livewire::test(SupportContactForm::class)
            ->set('name', 'Jan Janssen')
            ->set('email', 'jan@example.com')
            ->set('message', 'Ik wil graag bijdragen aan het platform.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        Mail::assertQueued(SupportMessage::class, function (SupportMessage $mail) {
            return $mail->senderName === 'Jan Janssen'
                && $mail->senderEmail === 'jan@example.com'
                && $mail->hasTo(config('mail.support_address'));
        });
    }

    public function test_support_form_is_rate_limited(): void
    {
        Mail::fake();

        $component = Livewire::test(SupportContactForm::class);

        for ($i = 0; $i < 3; $i++) {
            $component
                ->set('name', 'Jan')
                ->set('email', 'jan@example.com')
                ->set('message', "Bericht $i")
                ->call('send')
                ->assertHasNoErrors();

            // Reset sent state to allow resending
            $component->set('sent', false);
        }

        // 4th attempt should be throttled
        $component
            ->set('name', 'Jan')
            ->set('email', 'jan@example.com')
            ->set('message', 'Nog een bericht')
            ->call('send')
            ->assertHasErrors(['throttle']);

        Mail::assertQueued(SupportMessage::class, 3);
    }
}
