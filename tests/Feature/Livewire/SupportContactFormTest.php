<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SupportContactForm;
use App\Mail\SupportMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class SupportContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_reason_is_preselected(): void
    {
        Livewire::test(SupportContactForm::class, ['reason' => 'feedback'])
            ->assertSet('reason', 'feedback');
    }

    public function test_unknown_reason_is_discarded(): void
    {
        // Livewire pre-assigns the matching mount param to $reason before mount()
        // runs; the guard in mount() must reset an unknown slug back to ''.
        Livewire::test(SupportContactForm::class, ['reason' => 'banana'])
            ->assertSet('reason', '');
    }

    public function test_empty_reason_mounts_without_error(): void
    {
        // The contact view passes `$reason ?? ''`, so a missing query param arrives
        // as '' — the typed string property must accept it without a TypeError.
        Livewire::test(SupportContactForm::class, ['reason' => ''])
            ->assertSet('reason', '')
            ->assertOk();
    }

    public function test_successful_send_shows_a_personal_thank_you_with_first_name(): void
    {
        Mail::fake();

        Livewire::test(SupportContactForm::class)
            ->set('reason', 'feedback')
            ->set('name', 'Marie Peeters')
            ->set('email', 'marie@example.be')
            ->set('message', 'Wat een fijn platform!')
            ->call('send')
            ->assertSet('sent', true)
            ->assertSeeHtml('Bedankt, Marie!')
            ->assertSee('Je bericht is verstuurd')
            ->assertSee('marie@example.be');

        Mail::assertQueued(SupportMessage::class);
    }
}
