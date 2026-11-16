<?php

namespace App\Jobs;

use App\Services\Matching\BestScoreRecorder;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Recalculates every record's stored best score, after a sync changed the data. Unique until it
 * starts, so syncs finishing close together queue one run, and a sync finishing during a run
 * still queues the next.
 */
class RefreshBestScores implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 3600;

    public function handle(BestScoreRecorder $recorder): void
    {
        $recorder->refreshAll();
    }
}
