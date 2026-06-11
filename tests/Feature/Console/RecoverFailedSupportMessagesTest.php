<?php

namespace Tests\Feature\Console;

use App\Mail\SupportMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecoverFailedSupportMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function insertFailedSupportJob(string $name, string $email, string $message, ?Carbon $failedAt = null): void
    {
        $job = new SendQueuedMailable(new SupportMessage(
            senderName: $name,
            senderEmail: $email,
            senderMessage: $message,
        ));

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => SendQueuedMailable::class,
                'data' => [
                    'commandName' => SendQueuedMailable::class,
                    'command' => serialize($job),
                ],
            ]),
            'exception' => 'Attempt to read property "address" on null',
            'failed_at' => $failedAt ?? now(),
        ]);
    }

    public function test_it_recovers_sender_details_from_failed_jobs(): void
    {
        $this->insertFailedSupportJob('Marie Peeters', 'marie@voorbeeld.be', 'Ik wil graag een gift doen.');

        $exitCode = Artisan::call('support:recover-failed');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Marie Peeters', $output);
        $this->assertStringContainsString('marie@voorbeeld.be', $output);
        $this->assertStringContainsString('Ik wil graag een gift doen.', $output);
    }

    public function test_it_outputs_recovered_messages_as_json(): void
    {
        $this->insertFailedSupportJob('Jan Janssen', 'jan@voorbeeld.be', 'Bedankt voor dit platform.');

        Artisan::call('support:recover-failed', ['--json' => true]);
        $decoded = json_decode(Artisan::output(), true);

        $this->assertCount(1, $decoded);
        $this->assertSame('Jan Janssen', $decoded[0]['name']);
        $this->assertSame('jan@voorbeeld.be', $decoded[0]['email']);
        $this->assertSame('Bedankt voor dit platform.', $decoded[0]['message']);
    }

    public function test_it_recovers_messages_in_chronological_order(): void
    {
        $this->insertFailedSupportJob('Tweede', 'tweede@voorbeeld.be', 'Later bericht.', now()->subDay());
        $this->insertFailedSupportJob('Eerste', 'eerste@voorbeeld.be', 'Vroeger bericht.', now()->subDays(3));

        Artisan::call('support:recover-failed', ['--json' => true]);
        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame('Eerste', $decoded[0]['name']);
        $this->assertSame('Tweede', $decoded[1]['name']);
    }

    public function test_it_ignores_unrelated_failed_jobs(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\AssessFicheQuality', 'data' => ['command' => 'irrelevant']]),
            'exception' => 'boom',
            'failed_at' => now(),
        ]);

        Artisan::call('support:recover-failed');

        $this->assertStringContainsString('No failed support-form messages found.', Artisan::output());
    }
}
