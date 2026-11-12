<?php

namespace App\Enums;

/**
 * What happened to a match, as recorded in match_events.
 */
enum MatchAction: string
{
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Unmatched = 'unmatched';
    case FlaggedForReview = 'flagged_for_review';
    case Reviewed = 'reviewed';

    public function label(): string
    {
        return __('matches.actions.'.$this->value);
    }
}
