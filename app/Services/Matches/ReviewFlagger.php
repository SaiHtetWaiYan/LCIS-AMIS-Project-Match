<?php

namespace App\Services\Matches;

use App\Enums\MatchAction;
use App\Enums\MatchStatus;
use App\Models\MatchEvent;
use App\Models\ProjectMatch;
use Illuminate\Support\Facades\DB;

/**
 * After a sync, flags active matches whose records disappeared from their source, and confirmed
 * matches whose records' key matching fields changed since confirmation.
 */
class ReviewFlagger
{
    public function flag(): int
    {
        $flagged = 0;

        ProjectMatch::query()
            ->active()
            ->with(['lcisProject:id,missing_since,key_fields_hash', 'amisContract:id,missing_since,key_fields_hash'])
            ->each(function (ProjectMatch $match) use (&$flagged): void {
                $reasons = $this->reasons($match);
                $newReasons = array_values(array_diff($reasons, $match->review_reasons ?? []));

                if ($newReasons === []) {
                    return;
                }

                DB::transaction(function () use ($match, $reasons, $newReasons): void {
                    $match->forceFill([
                        'needs_review' => true,
                        'review_reasons' => array_values(array_unique([...($match->review_reasons ?? []), ...$reasons])),
                    ])->save();

                    $event = new MatchEvent;
                    $event->forceFill([
                        'project_match_id' => $match->id,
                        'action' => MatchAction::FlaggedForReview,
                        'from_status' => $match->status,
                        'to_status' => $match->status,
                        'reason' => implode('; ', array_map(fn (string $reason): string => __('matches.review_reasons.'.$reason), $newReasons)),
                        'created_at' => now(),
                    ])->save();
                });

                $flagged++;
            });

        return $flagged;
    }

    /**
     * @return list<string>
     */
    private function reasons(ProjectMatch $match): array
    {
        $isConfirmed = $match->status === MatchStatus::Matched;

        return array_keys(array_filter([
            'lcis_missing' => $match->lcisProject->missing_since !== null,
            'amis_missing' => $match->amisContract->missing_since !== null,
            'lcis_changed' => $isConfirmed && $match->lcis_hash_at_match !== null && $match->lcis_hash_at_match !== $match->lcisProject->key_fields_hash,
            'amis_changed' => $isConfirmed && $match->amis_hash_at_match !== null && $match->amis_hash_at_match !== $match->amisContract->key_fields_hash,
        ]));
    }
}
