<?php

namespace App\Services;

use App\Models\Fiche;
use App\Models\File;
use App\Models\PendingNotification;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Permanently removes a fiche and every trace of it: the uploaded files and
 * their derived previews, thumbnails and zips on the public disk, plus all
 * rows that reference it.
 *
 * Built for takedown requests (copyright complaints), where a soft delete is
 * not enough — the files stay publicly downloadable under /storage. Disk
 * artifacts are removed before the rows so an interrupted purge still leaves
 * the offending file gone; re-running the purge is safe.
 */
class FichePurger
{
    /**
     * @return array{files: int, images: int, comments: int, likes: int}
     */
    public function purge(Fiche $fiche): array
    {
        $files = $fiche->files()->get();

        $paths = [];
        foreach ($files as $file) {
            $paths = array_merge($paths, $this->artifactPaths($file));
        }

        $imageCount = count($paths) - $files->count();

        if ($fiche->zip_path) {
            $paths[] = $fiche->zip_path;
        }

        $this->deletePaths($paths);

        $comments = $fiche->comments()->withTrashed()->forceDelete();
        $likes = $fiche->likes()->delete();

        $fiche->tags()->detach();
        $fiche->themes()->detach();
        PendingNotification::where('fiche_id', $fiche->id)->delete();

        $fiche->files()->delete();
        $fiche->forceDelete();

        return [
            'files' => $files->count(),
            'images' => $imageCount,
            'comments' => $comments,
            'likes' => $likes,
        ];
    }

    /**
     * Remove a single file plus its previews and thumbnails.
     *
     * @return int The number of preview and thumbnail images removed.
     */
    public function purgeFile(File $file): int
    {
        $paths = $this->artifactPaths($file);

        $this->deletePaths($paths);
        $file->delete();

        return count($paths) - 1;
    }

    /**
     * Every path on the public disk that belongs to this file.
     *
     * @return array<int, string>
     */
    private function artifactPaths(File $file): array
    {
        return array_merge(
            [$file->path],
            $file->preview_images ?? [],
            $file->thumbnailPaths(),
        );
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deletePaths(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $disk = Storage::disk('public');

        $disk->delete($paths);
        $this->pruneEmptyDirectories($disk, $paths);
    }

    /**
     * Clean up per-file directories (files/media/4506, file-previews/4506) once
     * they run empty. Top-level directories are shared and never removed.
     *
     * @param  array<int, string>  $paths
     */
    private function pruneEmptyDirectories(Filesystem $disk, array $paths): void
    {
        $directories = array_unique(array_map('dirname', $paths));

        foreach ($directories as $directory) {
            if (! str_contains($directory, '/')) {
                continue;
            }

            if ($disk->files($directory) === [] && $disk->directories($directory) === []) {
                $disk->deleteDirectory($directory);
            }
        }
    }
}
