<?php

namespace App\Services\FicheStorage;

use App\Models\Fiche;
use App\Models\File;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

/**
 * Builds the response for "download this fiche". One local file is served
 * directly; one remote file becomes a short presigned redirect; several
 * files become a zip built in a private workspace.
 */
class FicheDownloader
{
    public function __construct(
        private FicheStorage $storage,
    ) {}

    /**
     * @param  Collection<int, File>  $files
     *
     * @throws DownloadBusy
     * @throws ObjectMissing
     */
    public function respond(Fiche $fiche, Collection $files): BinaryFileResponse|RedirectResponse
    {
        if ($files->count() === 1) {
            return $this->single($files->first());
        }

        return response()->download($this->buildZip($files), $fiche->slug.'-bestanden.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    private function single(File $file): BinaryFileResponse|RedirectResponse
    {
        $found = $this->storage->locate(FicheStorage::FILES, $file->path)
            ?? throw ObjectMissing::at(FicheStorage::FILES, $file->path);

        if ($found['location'] === 'local') {
            return response()->download($found['disk']->path($file->path), $file->original_filename);
        }

        return redirect()->away($this->storage->temporaryDownloadUrl($file->path, $file->original_filename, $file->mime_type));
    }

    /**
     * Gather every source in one workspace and keep them until the archive
     * is closed: ZipArchive reads its sources only on close(). Returns the
     * zip path; the caller sends and deletes it.
     *
     * @param  Collection<int, File>  $files
     */
    public function buildZip(Collection $files): string
    {
        $slot = $this->acquireSlot() ?? throw new DownloadBusy('All zip slots are taken.');

        try {
            $root = $this->storage->workspaceRoot();
            $needed = (int) $files->sum('size_bytes') * 2;
            $free = (int) @disk_free_space($root);

            if ($free - $needed < (int) config('fiche-storage.downloads.min_free_bytes')) {
                report(new RuntimeException("Zip download refused: {$free} bytes free, {$needed} needed."));

                throw new DownloadBusy('Not enough free disk space for another zip.');
            }

            $zipPath = $root.'/zip-'.now()->format('YmdHis').'-'.Str::lower(Str::random(12)).'.zip';

            try {
                $this->storage->withWorkspace(function (string $workspace) use ($files, $zipPath) {
                    $zip = new ZipArchive;

                    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                        throw new RuntimeException("Could not create zip {$zipPath}.");
                    }

                    $names = [];

                    foreach ($files->values() as $index => $file) {
                        $source = $this->localSource($file, $workspace.'/'.$index.'-'.Str::lower(Str::random(6)));
                        $name = $this->uniqueName($file->original_filename, $names);

                        if (! $zip->addFile($source, $name)) {
                            throw new RuntimeException("Could not add {$file->path} to zip.");
                        }
                    }

                    if (! $zip->close()) {
                        throw new RuntimeException("Could not write zip {$zipPath}.");
                    }
                });
            } catch (Throwable $e) {
                @unlink($zipPath);

                throw $e;
            }

            return $zipPath;
        } finally {
            $slot->release();
        }
    }

    private function localSource(File $file, string $fallbackPath): string
    {
        $found = $this->storage->locate(FicheStorage::FILES, $file->path)
            ?? throw ObjectMissing::at(FicheStorage::FILES, $file->path);

        if ($found['location'] === 'local') {
            return $found['disk']->path($file->path);
        }

        $this->storage->download($found['disk'], $file->path, $fallbackPath);

        return $fallbackPath;
    }

    /**
     * Two files with the same name must not overwrite each other in the zip:
     * the second becomes "naam (2).pdf".
     *
     * @param  array<string, true>  $taken
     */
    private function uniqueName(string $filename, array &$taken): string
    {
        $filename = trim(str_replace(['/', '\\'], '-', $filename)) ?: 'bestand';
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $candidate = $filename;

        for ($i = 2; isset($taken[mb_strtolower($candidate)]); $i++) {
            $candidate = $base." ({$i})".($extension !== '' ? '.'.$extension : '');
        }

        $taken[mb_strtolower($candidate)] = true;

        return $candidate;
    }

    private function acquireSlot(): ?Lock
    {
        for ($slot = 0; $slot < (int) config('fiche-storage.downloads.max_concurrent_zips'); $slot++) {
            $lock = Cache::lock("fiche-zip-slot:{$slot}", 600);

            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }
}
