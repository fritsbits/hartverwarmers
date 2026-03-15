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

    public function handle(): int
    {
        $sourcePath = rtrim($this->argument('source'), '/');

        // Load author → avatar media mapping from old DB
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

        $this->info("Found {$authorAvatars->count()} author avatars in old DB");

        // Build lookup maps for new users
        $newUsersByEmail = DB::table('users')
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn ($u) => strtolower(trim($u->email)));

        $stubUsersByName = DB::table('users')
            ->where('email', 'like', '%@import.hartverwarmers.be')
            ->get()
            ->keyBy(fn ($u) => strtolower(trim($u->first_name.' '.$u->last_name)));

        $imported = 0;
        $skippedHasAvatar = 0;
        $skippedNoUser = 0;
        $skippedNoFile = 0;

        foreach ($authorAvatars as $avatar) {
            // Find matching new user
            $newUser = null;

            // Try by email first
            $email = null;
            if ($avatar->user_email) {
                $email = strtolower(trim($avatar->user_email));
            } elseif ($avatar->author_email) {
                $email = strtolower(trim(explode(',', $avatar->author_email)[0]));
            }

            if ($email) {
                $newUser = $newUsersByEmail[$email] ?? null;
            }

            // Fall back to stub user by name
            if (! $newUser) {
                $newUser = $stubUsersByName[strtolower(trim($avatar->name))] ?? null;
            }

            if (! $newUser) {
                $skippedNoUser++;
                $this->line("  No user match: {$avatar->name}");

                continue;
            }

            // Skip if user already has an avatar
            if ($newUser->avatar_path) {
                $skippedHasAvatar++;

                continue;
            }

            // Check if source file exists
            $sourceFile = "{$sourcePath}/{$avatar->media_id}/{$avatar->file_name}";
            if (! file_exists($sourceFile)) {
                $skippedNoFile++;
                $this->warn("  Missing file: {$sourceFile}");

                continue;
            }

            // Determine extension
            $ext = strtolower(pathinfo($avatar->file_name, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }

            // Copy and resize avatar
            $avatarRelativePath = "avatars/{$newUser->id}.{$ext}";
            $thumbRelativePath = "avatars/{$newUser->id}-thumb.{$ext}";

            try {
                // Resize to max 400x400 for avatar
                $img = new Imagick($sourceFile);
                $img->setImageFormat($ext === 'jpg' ? 'jpeg' : $ext);
                $img->thumbnailImage(400, 400, true);
                Storage::disk('public')->put($avatarRelativePath, $img->getImageBlob());

                // Generate thumbnail (80x80)
                $img->thumbnailImage(80, 80, true);
                Storage::disk('public')->put($thumbRelativePath, $img->getImageBlob());
                $img->destroy();

                // Update user
                DB::table('users')
                    ->where('id', $newUser->id)
                    ->update(['avatar_path' => $avatarRelativePath]);

                $imported++;
                $this->line("  Imported: {$avatar->name} → {$avatarRelativePath}");
            } catch (\Throwable $e) {
                $this->warn("  Error processing {$avatar->name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Imported', $imported],
            ['Skipped (already has avatar)', $skippedHasAvatar],
            ['Skipped (no matching user)', $skippedNoUser],
            ['Skipped (file not found)', $skippedNoFile],
        ]);

        return self::SUCCESS;
    }
}
