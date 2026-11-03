<?php

namespace Tests\Feature\FicheStorage;

use App\Notifications\ServerHealthAlertNotification;
use App\Services\FicheStorage\BucketHealth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ServerHealthBucketsTest extends TestCase
{
    use PreparesFicheStorageScenarios;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram-bot-api.token' => 'test-token',
            'services.telegram-bot-api.chat_id' => '12345',
            'health.thresholds.memory_percent.critical' => 101,
            'health.thresholds.disk_percent.critical' => 101,
            'health.thresholds.load_average.critical' => 1_000_000,
        ]);
        Notification::fake();
    }

    /**
     * @return array<string, array{value: string, threshold: string}>
     */
    private function sentViolations(): array
    {
        $violations = [];

        Notification::assertSentOnDemand(ServerHealthAlertNotification::class, function (ServerHealthAlertNotification $notification) use (&$violations) {
            $violations = $notification->violations;

            return true;
        });

        return $violations;
    }

    public function test_a_missing_sentinel_on_the_files_bucket_triggers_an_alert(): void
    {
        $this->fakeFicheStorage('mirror');
        $this->remotePreviews()->put(config('fiche-storage.health.sentinel'), 'sentinel');

        $this->artisan('server:health-check')->assertExitCode(0);

        $violations = $this->sentViolations();
        $this->assertSame(['Bucket bestanden'], array_keys($violations));
        $this->assertStringContainsString('Sentinel ontbreekt', $violations['Bucket bestanden']['value']);
        $this->assertSame('sentinel leesbaar', $violations['Bucket bestanden']['threshold']);
    }

    public function test_a_missing_sentinel_on_the_previews_bucket_triggers_an_alert(): void
    {
        $this->fakeFicheStorage('s3-fallback');
        $this->remoteFiles()->put(config('fiche-storage.health.sentinel'), 'sentinel');

        $this->artisan('server:health-check')->assertExitCode(0);

        $this->assertSame(['Bucket previews'], array_keys($this->sentViolations()));
    }

    public function test_healthy_buckets_send_no_alert(): void
    {
        $this->fakeFicheStorage('mirror');
        $this->putSentinels();

        $this->artisan('server:health-check')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_the_alert_is_sent_once_and_then_held_back_by_the_cooldown(): void
    {
        $this->fakeFicheStorage('mirror');

        $this->artisan('server:health-check');
        $this->artisan('server:health-check');

        Notification::assertSentOnDemandTimes(ServerHealthAlertNotification::class, 1);
    }

    public function test_a_recovery_is_sent_once_the_buckets_are_back(): void
    {
        $this->fakeFicheStorage('mirror');
        $this->artisan('server:health-check');

        $this->putSentinels();
        $this->artisan('server:health-check');

        Notification::assertSentOnDemand(ServerHealthAlertNotification::class, fn (ServerHealthAlertNotification $notification) => $notification->recovered);
    }

    public function test_buckets_are_not_checked_in_local_mode(): void
    {
        $this->fakeFicheStorage('local');
        $this->mock(BucketHealth::class)->shouldNotReceive('check');

        $this->artisan('server:health-check')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_buckets_are_not_checked_without_remote_credentials(): void
    {
        $this->fakeFicheStorage('mirror');
        config(['filesystems.disks.fiche-files-s3.secret' => null]);
        $this->mock(BucketHealth::class)->shouldNotReceive('check');

        $this->artisan('server:health-check')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_nothing_is_checked_without_telegram_configuration(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->fakeFicheStorage('mirror');
        $this->mock(BucketHealth::class)->shouldNotReceive('check');

        $this->artisan('server:health-check')->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
