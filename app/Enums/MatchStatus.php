<?php

namespace App\Enums;

/**
 * Lifecycle of a proposed LCIS ↔ AMIS pair. Proposed and Matched are "active": they hold both
 * records (see MatchCardinality). Withdrawn was added to the agreed four for the proposer's own retraction.
 */
enum MatchStatus: string
{
    case Proposed = 'proposed';
    case Matched = 'matched';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Unmatched = 'unmatched';

    public function label(): string
    {
        return __('matches.statuses.'.$this->value);
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Proposed, self::Matched];
    }

    public function tone(): string
    {
        return match ($this) {
            self::Proposed => 'blue',
            self::Matched => 'green',
            self::Rejected => 'red',
            self::Withdrawn, self::Unmatched => 'gray',
        };
    }
}
