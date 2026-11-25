<?php

namespace Tests\Concerns;

use App\Services\FicheStorage\ObjectVersions;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * Fakes the four fiche disks the way production lays them out: both local
 * disks share one root, and each bucket has independent storage that
 * refuses path(). Remote credentials are filled in so every mode works.
 */
trait FakesFicheStorage
{
    protected FakeObjectVersions $objectVersions;

    protected function fakeFicheStorage(string $mode = 'local'): void
    {
        config(['fiche-storage.mode' => $mode]);

        $local = Storage::fake('fiche-files-local', ['throw' => true]);
        Storage::set('fiche-previews-local', Storage::build([
            'driver' => 'local',
            'root' => $local->path(''),
            'url' => rtrim((string) config('app.url'), '/').'/storage',
            'throw' => true,
        ]));

        foreach (['fiche-files-s3' => 'hartverwarmers-files', 'fiche-previews-s3' => 'hartverwarmers-previews'] as $disk => $bucket) {
            config([
                "filesystems.disks.{$disk}.key" => 'test-key',
                "filesystems.disks.{$disk}.secret" => 'test-secret',
                "filesystems.disks.{$disk}.bucket" => $bucket,
            ]);

            $fake = Storage::fake($disk, ['throw' => true]);
            Storage::set($disk, new RemoteFakeDisk($fake->getDriver(), $fake->getAdapter(), ['bucket' => $bucket, 'throw' => true]));
        }

        $this->objectVersions = new FakeObjectVersions;
        $this->app->instance(ObjectVersions::class, $this->objectVersions);
    }

    /**
     * Swap a remote fake bucket for one that throws on the given operations
     * (writeStream, delete). Keeps the bytes already stored in it.
     */
    protected function breakRemote(string $disk, string ...$operations): void
    {
        $current = Storage::disk($disk);
        $broken = new FailingRemoteFakeDisk($current->getDriver(), $current->getAdapter(), ['bucket' => config("filesystems.disks.{$disk}.bucket"), 'throw' => true]);
        $broken->failing = $operations;

        Storage::set($disk, $broken);
    }

    protected function useFicheStorageMode(string $mode): void
    {
        config(['fiche-storage.mode' => $mode]);
    }

    protected function localFiles(): FilesystemAdapter
    {
        return Storage::disk('fiche-files-local');
    }

    protected function localPreviews(): FilesystemAdapter
    {
        return Storage::disk('fiche-previews-local');
    }

    protected function remoteFiles(): FilesystemAdapter
    {
        return Storage::disk('fiche-files-s3');
    }

    protected function remotePreviews(): FilesystemAdapter
    {
        return Storage::disk('fiche-previews-s3');
    }
}
