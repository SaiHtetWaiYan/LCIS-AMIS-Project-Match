<?php

namespace App\Console\Commands;

use App\Services\Matching\BestScoreRecorder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Recalculates the stored best scores in this process, e.g. after deploying a scoring change.
 * Syncs queue the same work (RefreshBestScores job).
 */
#[Signature('matching:score')]
#[Description('Recalculate every record’s best match score shown on the lists')]
class RecalculateBestScores extends Command
{
    public function handle(BestScoreRecorder $recorder): int
    {
        $counts = $recorder->refreshAll();

        $this->components->twoColumnDetail('LCIS', $counts['lcis'].' projects');
        $this->components->twoColumnDetail('AMIS', $counts['amis'].' contracts');

        return self::SUCCESS;
    }
}
