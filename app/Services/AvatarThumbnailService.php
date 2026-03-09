<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Imagick;

class AvatarThumbnailService
{
    private const THUMB_SIZE = 256;

    private const THUMB_QUALITY = 80;

    /**
     * Generate a square WebP thumbnail for an avatar.
     *
     * @return string The relative path to the generated thumbnail
     */
    public function generate(string $avatarPath): string
    {
        $absolutePath = Storage::disk('public')->path($avatarPath);
        $thumbRelative = preg_replace('/\.(jpe?g|png|webp)$/i', '-thumb.webp', $avatarPath);
        $thumbAbsolute = Storage::disk('public')->path($thumbRelative);

        $im = new Imagick($absolutePath);
        $im->setImageFormat('webp');
        $im->setImageCompressionQuality(self::THUMB_QUALITY);
        $im->cropThumbnailImage(self::THUMB_SIZE, self::THUMB_SIZE);
        $im->writeImage($thumbAbsolute);
        $im->destroy();

        return $thumbRelative;
    }

    /**
     * Delete the thumbnail for a given avatar path.
     */
    public function delete(string $avatarPath): void
    {
        $thumbPath = preg_replace('/\.(jpe?g|png|webp)$/i', '-thumb.webp', $avatarPath);
        Storage::disk('public')->delete($thumbPath);
    }
}
