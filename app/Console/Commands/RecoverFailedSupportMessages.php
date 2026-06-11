<?php

namespace App\Console\Commands;

use App\Mail\SupportMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecoverFailedSupportMessages extends Command
{
    protected $signature = 'support:recover-failed {--json : Output the recovered messages as JSON}';

    protected $description = 'Extract contact-form messages from failed queue jobs (recipient was unset, so they never sent)';

    public function handle(): int
    {
        $rows = DB::table('failed_jobs')
            ->where('payload', 'like', '%SupportMessage%')
            ->orderBy('failed_at')
            ->get(['uuid', 'payload', 'failed_at']);

        $recovered = [];

        foreach ($rows as $row) {
            $mailable = $this->extractMailable($row->payload);

            if (! $mailable instanceof SupportMessage) {
                continue;
            }

            $recovered[] = [
                'failed_at' => $row->failed_at,
                'name' => $mailable->senderName,
                'email' => $mailable->senderEmail,
                'message' => $mailable->senderMessage,
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($recovered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($recovered === []) {
            $this->components->info('No failed support-form messages found.');

            return self::SUCCESS;
        }

        $this->components->info(count($recovered).' failed support-form message(s) recovered:');

        foreach ($recovered as $i => $message) {
            $this->newLine();
            $this->line('  <fg=yellow>'.($i + 1).'. '.$message['name'].'</>');
            $this->line('  '.$message['email'].'  <fg=gray>('.$message['failed_at'].')</>');
            $this->newLine();
            $this->line('  '.str_replace("\n", "\n  ", trim($message['message'])));
            $this->line('  <fg=gray>'.str_repeat('-', 60).'</>');
        }

        return self::SUCCESS;
    }

    private function extractMailable(string $payload): ?object
    {
        try {
            $data = json_decode($payload, true);
            $command = $data['data']['command'] ?? null;

            if (! is_string($command)) {
                return null;
            }

            $job = unserialize($command);

            return $job->mailable ?? null;
        } catch (Throwable) {
            return null;
        }
    }
}
