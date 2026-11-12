<?php

namespace Tests\Feature\Services\Matching;

use App\Enums\SyncSource;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Services\Matching\MatchPair;
use App\Services\Matching\MatchTermIndex;
use App\Services\Matching\TextSimilarity;

/**
 * Builds a MatchPair the way CandidateFinder does: index both sides, then fetch text similarities.
 */
trait BuildsMatchPairs
{
    private function pairFor(LcisProject $project, AmisContract $contract): MatchPair
    {
        app(MatchTermIndex::class)->rebuild(SyncSource::Lcis);
        app(MatchTermIndex::class)->rebuild(SyncSource::Amis);

        $textMatches = app(TextSimilarity::class)->bestMatches(SyncSource::Lcis, $project->id, [$contract->id]);

        return new MatchPair(
            $project->fresh(['locations', 'company']),
            $contract->fresh(['items', 'companies']),
            $textMatches[$contract->id] ?? [],
        );
    }
}
