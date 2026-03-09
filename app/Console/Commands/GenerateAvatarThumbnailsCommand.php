<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AvatarThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateAvatarThumbnailsCommand extends Command
{
    protected $signature = 'avatar:generate-thumbnails';

    protected $description = 'Generate thumbnails for existing user avatars';

    public function handle(AvatarThumbnailService $thumbnailService): int
    {
        $users = User::whereNotNull('avatar_path')->get();
        $this->info("Found {$users->count()} users with avatars.");

        $generated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $thumbPath = preg_replace('/\.(jpe?g|png|webp)$/i', '-thumb.webp', $user->avatar_path);

            if (Storage::disk('public')->exists($thumbPath)) {
                $skipped++;

                continue;
            }

            if (! Storage::disk('public')->exists($user->avatar_path)) {
                $this->warn("  Missing: {$user->avatar_path} (user #{$user->id})");

                continue;
            }

            try {
                $thumbnailService->generate($user->avatar_path);
                $generated++;
                $this->info("  Generated: {$thumbPath}");
            } catch (\Throwable $e) {
                $this->error("  Failed for user #{$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Generated: {$generated}, Skipped (already exists): {$skipped}.");

        return self::SUCCESS;
    }
}
