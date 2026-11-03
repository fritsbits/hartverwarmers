<?php

namespace App\Services;

use App\Models\Fiche;
use App\Models\File;
use App\Models\PendingNotification;
use App\Services\FicheStorage\FicheStorage;

/**
 * Permanently removes a fiche and every trace of it: the uploaded files and
 * converted pdf's, their previews and thumbnails, a legacy pre-built zip,
 * plus all rows that reference it.
 *
 * Built for takedown requests (copyright complaints), where a soft delete is
 * not enough. Objects go from local storage and from the bucket, including
 * every stored version, before the rows go: a storage failure throws and
 * leaves the rows in place, so re-running the purge is safe.
 */
class FichePurger
{
    public function __construct(
        private FicheStorage $storage,
    ) {}

    /**
     * @return array{files: int, images: int, comments: int, likes: int}
     */
    public function purge(Fiche $fiche): array
    {
        $files = $fiche->files()->get();

        $imageCount = 0;
        foreach ($files as $file) {
            $imageCount += $this->purgeArtifacts($file);
        }

        if ($fiche->zip_path) {
            $this->storage->localDisk(FicheStorage::FILES)->delete($fiche->zip_path);
        }

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
        $images = $this->purgeArtifacts($file);
        $file->delete();

        return $images;
    }

    /**
     * @return int The number of preview and thumbnail images removed.
     */
    private function purgeArtifacts(File $file): int
    {
        $previews = array_merge($file->preview_images ?? [], $file->thumbnailPaths());

        $this->storage->purge(FicheStorage::FILES, [$file->path]);
        $this->storage->purge(FicheStorage::PREVIEWS, $previews);

        return count($previews);
    }
}
