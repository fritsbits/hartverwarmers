<?php

namespace App\Console\Commands;

use App\Jobs\QueueHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckQueueHealth extends Command
{
    protected $signature = 'queue:heartbeat';

    protected $description = 'Dispatch a heartbeat job and alert if the previous one was missed';

    public function handle(): void
    {
        $lastBeat = Cache::get('queue-heartbeat');
        $alertSent = Cache::get('queue-heartbeat:alerted');

        if ($lastBeat === null || (now()->timestamp - $lastBeat) > 600) {
            $this->components->error('Queue heartbeat missed — worker may be down.');
            Log::warning('Queue heartbeat missed', [
                'last_beat' => $lastBeat ? now()->createFromTimestamp($lastBeat)->toDateTimeString() : 'never',
            ]);

            if (! $alertSent) {
                $this->sendAlert($lastBeat);
                Cache::put('queue-heartbeat:alerted', true, 3600);
            }
        } else {
            $this->components->info('Queue heartbeat OK.');

            if ($alertSent) {
                Cache::forget('queue-heartbeat:alerted');
            }
        }

        QueueHeartbeat::dispatch();
    }

    private function sendAlert(?int $lastBeat): void
    {
        $lastBeatFormatted = $lastBeat
            ? now()->createFromTimestamp($lastBeat)->toDateTimeString()
            : 'nooit';

        Mail::raw(
            "De queue worker lijkt niet meer te werken.\n\nLaatste heartbeat: {$lastBeatFormatted}\n\nControleer de queue worker in Forge.",
            function ($message) {
                $message->to(config('mail.admin_address', 'admin@example.com'))
                    ->subject('Hartverwarmers — Queue worker probleem');
            }
        );
    }
}
