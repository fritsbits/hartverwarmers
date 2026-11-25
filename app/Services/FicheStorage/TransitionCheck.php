<?php

namespace App\Services\FicheStorage;

use App\Enums\FicheStorageMode;

/**
 * Decides whether a release may go live with a given storage mode. Runs in
 * the Forge deploy script before the release is activated, so a failing
 * check aborts the deploy. A deploy that keeps the live mode only needs a
 * sane configuration; a mode change needs the evidence for that step.
 */
class TransitionCheck
{
    public function __construct(
        private FicheStorage $storage,
        private MigrationRegister $register,
        private WriterDrain $drain,
        private BucketHealth $buckets,
    ) {}

    /**
     * @return array{ok: bool, current: string, target: string, reasons: array<int, string>}
     */
    public function check(FicheStorageMode $target): array
    {
        $current = $this->drain->observedMode();
        $reasons = [];

        if ($target->usesRemote() && ! $this->storage->remoteConfigured()) {
            $reasons[] = 'De FICHE_STORAGE_*-gegevens in Forge Environment zijn onvolledig.';
        }

        if ($reasons === [] && $target !== $current) {
            $reasons = match ($target) {
                FicheStorageMode::Local => $this->toLocal($current),
                FicheStorageMode::Mirror => $this->smokeTestReasons(),
                FicheStorageMode::S3Fallback => $this->toS3Fallback($current),
                FicheStorageMode::S3 => $this->toS3($current),
            };
        }

        return ['ok' => $reasons === [], 'current' => $current->value, 'target' => $target->value, 'reasons' => $reasons];
    }

    /**
     * @return array<int, string>
     */
    private function toLocal(FicheStorageMode $current): array
    {
        if ($this->register->meta('s3_authoritative_since') === null) {
            return [];
        }

        if ($current !== FicheStorageMode::Mirror) {
            return ['Sinds de omschakeling staan nieuwe bestanden alleen in de bucket. Ga eerst terug naar mirror en voer files:copy-to-local --queued uit.'];
        }

        return $this->verifiedRunReasons(StorageMigration::TO_LOCAL, 'files:copy-to-local --queued');
    }

    /**
     * @return array<int, string>
     */
    private function toS3Fallback(FicheStorageMode $current): array
    {
        if ($current === FicheStorageMode::Local) {
            return ['Van local kan alleen naar mirror; kopieer en verifieer eerst alle bestanden.'];
        }

        $reasons = $this->smokeTestReasons();

        if ($current === FicheStorageMode::Mirror) {
            $reasons = array_merge($reasons, $this->verifiedRunReasons(StorageMigration::TO_REMOTE, 'files:copy-to-object-storage --queued'));
            $reasons = array_merge($reasons, $this->bucketReasons());
        }

        return $reasons;
    }

    /**
     * @return array<int, string>
     */
    private function toS3(FicheStorageMode $current): array
    {
        if ($current !== FicheStorageMode::S3Fallback) {
            return ['Naar s3 kan alleen vanuit s3-fallback.'];
        }

        $reasons = [];
        $firstSwitch = $this->register->meta('first_switch_at');
        $earliest = $firstSwitch === null ? null : $firstSwitch + (int) config('fiche-storage.migration.retention_days') * 86400;

        if ($earliest === null || now()->timestamp < $earliest) {
            $reasons[] = 'De bewaartermijn na de omschakeling loopt nog'.($earliest ? ' tot '.date('d-m-Y H:i', $earliest) : '').'.';
        }

        $hits = $this->register->unresolvedFallbackHits();
        if ($hits > 0) {
            $reasons[] = "{$hits} object(en) werden nog lokaal gelezen na hun laatste verificatie.";
        }

        return array_merge(
            $reasons,
            $this->verifiedRunReasons(StorageMigration::TO_REMOTE, 'files:copy-to-object-storage --queued'),
            $this->bucketReasons(),
        );
    }

    /**
     * The latest run of $kind verified everything, inventoried after every
     * process of the live mode took over, and nothing changed since.
     *
     * @return array<int, string>
     */
    private function verifiedRunReasons(string $kind, string $command): array
    {
        $drainedAt = $this->drain->drainedAt();

        if ($drainedAt === null) {
            return array_merge(['Oude processen zijn nog niet uitgelopen.'], $this->drain->evaluate()['reasons']);
        }

        $run = $this->register->latestRun($kind);
        $summary = json_decode($run->summary ?? '{}', true) ?: [];

        if ($run === null || $run->status !== 'verified' || ($summary['final_round_started_at'] ?? 0) < $drainedAt) {
            return ["Geen geslaagde verificatie sinds de processen uitliepen. Voer {$command} uit en wacht op 'verified'."];
        }

        $unverified = $kind === StorageMigration::TO_REMOTE ? $this->register->unverifiedCount() : 0;

        return $unverified > 0 ? ["{$unverified} object(en) wachten nog op verificatie."] : [];
    }

    /**
     * @return array<int, string>
     */
    private function smokeTestReasons(): array
    {
        $smoke = $this->register->meta('smoketest');

        if (($smoke['status'] ?? null) !== 'passed') {
            return ['De buckettest is niet geslaagd. Voer files:storage-prepare uit en wacht op het resultaat.'];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function bucketReasons(): array
    {
        return collect($this->buckets->check())
            ->reject(fn (array $result) => $result['ok'])
            ->map(fn (array $result, string $role) => "Bucket {$role} niet bereikbaar: {$result['error']}")
            ->values()
            ->all();
    }
}
