<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Imagick;

class ImportAvatars extends Command
{
    protected $signature = 'app:import-avatars {source : Path to the media backup folder}';

    protected $description = 'Import author avatars from old Soulcenter backup media';

    private int $imported = 0;

    private int $skippedHasAvatar = 0;

    private int $skippedNoUser = 0;

    private int $skippedNoFile = 0;

    public function handle(): int
    {
        $sourcePath = rtrim($this->argument('source'), '/');

        // Build lookup maps for new users
        $newUsersByEmail = DB::table('users')
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn ($u) => strtolower(trim($u->email)));

        $stubUsersByName = DB::table('users')
            ->where('email', 'like', '%@import.hartverwarmers.be')
            ->get()
            ->keyBy(fn ($u) => strtolower(trim($u->first_name.' '.$u->last_name)));

        // Pass 1: avatars linked via authors table
        $this->importViaAuthors($sourcePath, $newUsersByEmail, $stubUsersByName);

        // Pass 2: avatars linked via public profiles → users (catches registrations not in authors table)
        $this->importViaProfiles($sourcePath, $newUsersByEmail);

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Imported', $this->imported],
            ['Skipped (already has avatar)', $this->skippedHasAvatar],
            ['Skipped (no matching user)', $this->skippedNoUser],
            ['Skipped (file not found)', $this->skippedNoFile],
        ]);

        return self::SUCCESS;
    }

    private function importViaAuthors(string $sourcePath, $newUsersByEmail, $stubUsersByName): void
    {
        $authorAvatars = DB::connection('soulcenter')
            ->table('authors as a')
            ->join('media as m', function ($join) {
                $join->on('m.model_id', '=', 'a.profile_id')
                    ->where('m.model_type', 'App\\Models\\Profile')
                    ->where('m.collection_name', 'avatar');
            })
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->select('a.id as author_id', 'a.name', 'a.email as author_email',
                'u.email as user_email', 'm.id as media_id', 'm.file_name', 'm.mime_type')
            ->get();

        $this->info("Pass 1 — author avatars: {$authorAvatars->count()}");

        foreach ($authorAvatars as $avatar) {
            $newUser = null;

            $email = null;
            if ($avatar->user_email) {
                $email = strtolower(trim($avatar->user_email));
            } elseif ($avatar->author_email) {
                $email = strtolower(trim(explode(',', $avatar->author_email)[0]));
            }

            if ($email) {
                $newUser = $newUsersByEmail[$email] ?? null;
            }

            if (! $newUser) {
                $newUser = $stubUsersByName[strtolower(trim($avatar->name))] ?? null;
            }

            $this->importAvatar($newUser, $avatar->name, $sourcePath, $avatar->media_id, $avatar->file_name);
        }
    }

    private function importViaProfiles(string $sourcePath, $newUsersByEmail): void
    {
        $profileAvatars = DB::connection('soulcenter')
            ->table('media as m')
            ->join('profiles as p', 'm.model_id', '=', 'p.id')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->where('m.model_type', 'App\\Models\\Profile')
            ->where('m.collection_name', 'avatar')
            ->where('p.type', 'public')
            ->select('u.email', DB::raw("CONCAT(p.first_name, ' ', p.last_name) as name"),
                'm.id as media_id', 'm.file_name')
            ->get();

        $this->info("Pass 2 — public profile avatars: {$profileAvatars->count()}");

        foreach ($profileAvatars as $avatar) {
            $email = strtolower(trim($avatar->email));
            $newUser = $newUsersByEmail[$email] ?? null;

            $this->importAvatar($newUser, $avatar->name, $sourcePath, $avatar->media_id, $avatar->file_name);
        }
    }

    private function importAvatar($newUser, string $name, string $sourcePath, int $mediaId, string $fileName): void
    {
        if (! $newUser) {
            $this->skippedNoUser++;
            $this->line("  No user match: {$name}");

            return;
        }

        if ($newUser->avatar_path) {
            $this->skippedHasAvatar++;

            return;
        }

        $sourceFile = "{$sourcePath}/{$mediaId}/{$fileName}";
        if (! file_exists($sourceFile)) {
            $this->skippedNoFile++;
            $this->warn("  Missing file: {$sourceFile}");

            return;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }

        $avatarRelativePath = "avatars/{$newUser->id}.{$ext}";
        $thumbRelativePath = "avatars/{$newUser->id}-thumb.{$ext}";

        try {
            $img = new Imagick($sourceFile);
            $img->setImageFormat($ext === 'jpg' ? 'jpeg' : $ext);
            $img->thumbnailImage(400, 400, true);
            Storage::disk('public')->put($avatarRelativePath, $img->getImageBlob());

            $img->thumbnailImage(80, 80, true);
            Storage::disk('public')->put($thumbRelativePath, $img->getImageBlob());
            $img->destroy();

            DB::table('users')
                ->where('id', $newUser->id)
                ->update(['avatar_path' => $avatarRelativePath]);

            $this->imported++;
            $this->line("  Imported: {$name} → {$avatarRelativePath}");
        } catch (\Throwable $e) {
            $this->warn("  Error processing {$name}: {$e->getMessage()}");
        }
    }
}
