<?php

namespace App\Jobs;

use App\Enums\SyncSource;
use App\Services\Lcis\LcisSynchronizer;
use App\Services\Sync\SyncRunRecorder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Snapshots LCIS projects and companies into the local tables.
 * Runs nightly from the scheduler or on demand from the admin sync page. One instance per source
 * at a time; a failed run is recorded and not retried automatically.
 */
class SyncLcisProjects implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function __construct(public readonly ?int $triggeredBy = null) {}

    public function uniqueId(): string
    {
        return SyncSource::Lcis->value;
    }

    public function handle(SyncRunRecorder $recorder, LcisSynchronizer $synchronizer): void
    {
        $recorder->record(SyncSource::Lcis, $this->triggeredBy, $synchronizer->sync(...));
    }

    public function failed(?Throwable $exception): void
    {
        app(SyncRunRecorder::class)->failRunning(SyncSource::Lcis, $exception?->getMessage() ?? 'The sync job failed.');
    }
}
