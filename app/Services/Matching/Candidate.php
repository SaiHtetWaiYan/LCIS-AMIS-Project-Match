<?php

namespace App\Services\Matching;

use App\Models\AmisContract;
use App\Models\LcisProject;

/**
 * A suggested counterpart record with its score card.
 */
final readonly class Candidate
{
    public function __construct(
        public LcisProject|AmisContract $record,
        public ScoreCard $score,
    ) {}
}
