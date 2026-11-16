<?php

namespace App\Jobs;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Services\Amis\AmisSynchronizer;
use App\Services\Sync\SyncRunRecorder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Snapshots AMIS rent-land contracts, their items and the linked companies into the local tables.
 * Runs nightly from the scheduler or on demand from the admin sync page. One instance per source
 * at a time; a failed run is recorded and not retried automatically.
 */
class SyncAmisContracts implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(public readonly ?int $triggeredBy = null) {}

    public function uniqueId(): string
    {
        return SyncSource::Amis->value;
    }

    public function handle(SyncRunRecorder $recorder, AmisSynchronizer $synchronizer): void
    {
        $run = $recorder->record(SyncSource::Amis, $this->triggeredBy, $synchronizer->sync(...));

        if ($run->status === SyncStatus::Succeeded) {
            RefreshBestScores::dispatch();
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(SyncRunRecorder::class)->failRunning(SyncSource::Amis, $exception?->getMessage() ?? 'The sync job failed.');
    }
}
