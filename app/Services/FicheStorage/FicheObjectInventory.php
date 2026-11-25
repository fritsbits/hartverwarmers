<?php

namespace App\Services\FicheStorage;

use App\Models\File;

/**
 * Every storage object the database refers to: originals and converted
 * pdf's (files.path) plus preview images and their thumbnails. Covers all
 * file rows, also those of soft-deleted fiches and unattached wizard uploads.
 */
class FicheObjectInventory
{
    /**
     * @return array{objects: array<int, array{role: string, path: string}>, last_id: ?int}
     */
    public function chunkAfter(int $afterFileId, int $limit): array
    {
        $files = File::query()
            ->where('id', '>', $afterFileId)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'path', 'preview_images']);

        $objects = [];

        foreach ($files as $file) {
            $objects[] = ['role' => FicheStorage::FILES, 'path' => $file->path];

            foreach ($this->previewPaths($file) as $path) {
                $objects[] = ['role' => FicheStorage::PREVIEWS, 'path' => $path];
            }
        }

        return ['objects' => $objects, 'last_id' => $files->last()?->id];
    }

    public function isReferenced(string $role, string $path): bool
    {
        if ($role === FicheStorage::FILES) {
            return File::query()->where('path', $path)->exists();
        }

        $fileId = self::fileIdFromPreviewPath($path);

        if ($fileId === null) {
            return false;
        }

        $file = File::query()->find($fileId, ['id', 'preview_images']);

        return $file !== null && in_array($path, $this->previewPaths($file), true);
    }

    public static function fileIdFromPreviewPath(string $path): ?int
    {
        return preg_match('#^file-previews/(\d+)/#', $path, $matches) ? (int) $matches[1] : null;
    }

    /**
     * @return array<int, string>
     */
    private function previewPaths(File $file): array
    {
        return array_merge($file->preview_images ?? [], $file->thumbnailPaths());
    }
}
