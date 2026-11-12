<?php

namespace App\Services\Matching;

use App\Models\AmisContract;
use App\Models\LcisProject;

/**
 * An LCIS project and an AMIS contract being compared, with their best text matches precomputed
 * (pg_trgm runs in the database, so similarities are fetched in bulk before rules run).
 * The project needs locations loaded and the contract needs items loaded.
 */
final readonly class MatchPair
{
    /**
     * @param  array<string, TextMatch>  $textMatches  best match by kind; a kind is absent when either side has no text of it
     */
    public function __construct(
        public LcisProject $project,
        public AmisContract $contract,
        private array $textMatches,
    ) {}

    public function bestTextMatch(string $kind): ?TextMatch
    {
        return $this->textMatches[$kind] ?? null;
    }
}
