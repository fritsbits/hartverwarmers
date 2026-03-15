<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportUsers extends Command
{
    protected $signature = 'app:import-users {csv : Path to the contacts CSV file}';

    protected $description = 'Import users from contacts CSV and old DB interactors';

    public function handle(): int
    {
        $csvPath = $this->argument('csv');
        if (! file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");

            return self::FAILURE;
        }

        $existingEmails = DB::table('users')
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim($e)))
            ->flip();

        $oldUsersByEmail = $this->loadOldUsers();

        // Source A: CSV contacts
        $csvContacts = $this->parseCsv($csvPath);
        $this->info('CSV contacts loaded: '.count($csvContacts));

        $imported = 0;
        $skipped = 0;
        $noPassword = 0;

        DB::transaction(function () use ($csvContacts, $oldUsersByEmail, &$existingEmails, &$imported, &$skipped, &$noPassword) {
            foreach ($csvContacts as $contact) {
                $email = strtolower(trim($contact['email']));

                if ($existingEmails->has($email)) {
                    $skipped++;

                    continue;
                }

                $oldUser = $oldUsersByEmail[$email] ?? null;
                $password = $oldUser?->password ?? bcrypt(Str::random(32));
                if (! $oldUser) {
                    $noPassword++;
                }

                DB::table('users')->insert([
                    'first_name' => $contact['first_name'],
                    'last_name' => $contact['last_name'],
                    'email' => $contact['email'],
                    'password' => $password,
                    'role' => 'member',
                    'email_verified_at' => now(),
                    'created_at' => $oldUser?->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $existingEmails[$email] = true;
                $imported++;
            }
        });

        $this->info("CSV: {$imported} imported, {$skipped} skipped (existing), {$noPassword} without old password");

        // Source B: Additional interactors from old DB
        $interactorImported = $this->importInteractors($oldUsersByEmail, $existingEmails);

        $this->newLine();
        $this->table(['Source', 'Imported', 'Skipped'], [
            ['CSV contacts', $imported, $skipped],
            ['Old DB interactors', $interactorImported['imported'], $interactorImported['skipped']],
        ]);

        $total = $imported + $interactorImported['imported'];
        $this->info("Total users imported: {$total}");

        return self::SUCCESS;
    }

    private function loadOldUsers(): array
    {
        $users = DB::connection('soulcenter')
            ->table('users')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'password', 'created_at')
            ->get();

        $byEmail = [];
        foreach ($users as $user) {
            $byEmail[strtolower(trim($user->email))] = $user;
        }

        return $byEmail;
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            $data = array_combine($header, $row);
            $rows[] = $data;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Import users who interacted (commented/liked) but aren't in the CSV.
     */
    private function importInteractors(array $oldUsersByEmail, $existingEmails): array
    {
        $interactorIds = DB::connection('soulcenter')
            ->table('comments')
            ->where('commentable_type', 'App\\Models\\Activity')
            ->whereNull('deleted_at')
            ->pluck('user_id')
            ->merge(
                DB::connection('soulcenter')
                    ->table('likes')
                    ->where('likeable_type', 'App\\Models\\Activity')
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
            )
            ->unique();

        $interactorUsers = DB::connection('soulcenter')
            ->table('users')
            ->whereIn('id', $interactorIds)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'email', 'password', 'created_at')
            ->get();

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($interactorUsers, $existingEmails, &$imported, &$skipped) {
            foreach ($interactorUsers as $oldUser) {
                $email = strtolower(trim($oldUser->email));

                if ($existingEmails->has($email)) {
                    $skipped++;

                    continue;
                }

                $nameParts = $this->splitName($oldUser->name);

                DB::table('users')->insert([
                    'first_name' => $nameParts['first_name'],
                    'last_name' => $nameParts['last_name'],
                    'email' => $oldUser->email,
                    'password' => $oldUser->password,
                    'role' => 'member',
                    'email_verified_at' => now(),
                    'created_at' => $oldUser->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $existingEmails[$email] = true;
                $imported++;
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function splitName(?string $name): array
    {
        if (empty($name)) {
            return ['first_name' => '', 'last_name' => ''];
        }

        $parts = explode(' ', trim($name), 2);

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
        ];
    }
}
