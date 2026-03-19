<?php

namespace App\Console\Commands;

use App\Notifications\ServerHealthAlertNotification;
use App\Services\ServerHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class CheckServerHealth extends Command
{
    protected $signature = 'server:health-check';

    protected $description = 'Check server health metrics and alert via Telegram if thresholds are exceeded';

    public function handle(): int
    {
        if (! config('services.telegram-bot-api.token') || ! config('services.telegram-bot-api.chat_id')) {
            $this->components->warn('Telegram not configured — skipping health alerts.');

            return self::SUCCESS;
        }

        $violations = $this->checkThresholds();
        $cooldownMinutes = (int) config('health.alert_cooldown_minutes', 60);
        $hasExistingAlerts = $this->hasExistingAlerts();

        if ($violations->isEmpty()) {
            if ($hasExistingAlerts) {
                $this->sendRecovery();
                $this->clearAllAlertKeys();
                $this->components->info('Recovery notification sent.');
            } else {
                $this->components->info('All metrics healthy.');
            }

            return self::SUCCESS;
        }

        $newViolations = $violations->filter(
            fn ($data, $metric) => ! Cache::has("health-alert:{$metric}")
        );

        if ($newViolations->isNotEmpty()) {
            $this->sendAlert($newViolations->all());

            foreach ($violations as $metric => $data) {
                Cache::put("health-alert:{$metric}", true, $cooldownMinutes * 60);
            }

            $this->components->error('Health alert sent: '.$violations->keys()->implode(', '));
        } else {
            $this->components->warn('Thresholds exceeded but alert is in cooldown.');
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<string, array{value: string, threshold: string}>
     */
    private function checkThresholds(): Collection
    {
        $violations = collect();

        $memory = ServerHealth::memory();
        if ($memory) {
            $critical = config('health.thresholds.memory_percent.critical');
            if ($memory['percent'] >= $critical) {
                $violations['Geheugen'] = [
                    'value' => ServerHealth::formatBytes($memory['used']).' / '.ServerHealth::formatBytes($memory['total'])." ({$memory['percent']}%)",
                    'threshold' => "{$critical}%",
                ];
            }
        }

        $disk = ServerHealth::disk();
        $diskCritical = config('health.thresholds.disk_percent.critical');
        if ($disk['percent'] >= $diskCritical) {
            $violations['Schijf'] = [
                'value' => ServerHealth::formatBytes($disk['used']).' / '.ServerHealth::formatBytes($disk['total'])." ({$disk['percent']}%)",
                'threshold' => "{$diskCritical}%",
            ];
        }

        $load = ServerHealth::loadAverage();
        if ($load) {
            $loadCritical = config('health.thresholds.load_average.critical');
            if ($load['1m'] >= $loadCritical) {
                $violations['Load (1m)'] = [
                    'value' => (string) $load['1m'],
                    'threshold' => (string) $loadCritical,
                ];
            }
        }

        return $violations;
    }

    private function hasExistingAlerts(): bool
    {
        return Cache::has('health-alert:Geheugen')
            || Cache::has('health-alert:Schijf')
            || Cache::has('health-alert:Load (1m)');
    }

    private function clearAllAlertKeys(): void
    {
        Cache::forget('health-alert:Geheugen');
        Cache::forget('health-alert:Schijf');
        Cache::forget('health-alert:Load (1m)');
    }

    /** @param  array<string, array{value: string, threshold: string}>  $violations */
    private function sendAlert(array $violations): void
    {
        Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
            ->notify(new ServerHealthAlertNotification(violations: $violations));
    }

    private function sendRecovery(): void
    {
        Notification::route('telegram', config('services.telegram-bot-api.chat_id'))
            ->notify(new ServerHealthAlertNotification(recovered: true));
    }
}
