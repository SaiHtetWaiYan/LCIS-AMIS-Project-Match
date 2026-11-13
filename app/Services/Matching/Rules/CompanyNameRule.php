<?php

namespace App\Services\Matching\Rules;

use App\Services\Matching\MatchTermIndex;

/**
 * LCIS company name (Lao, and English from LCIS companies) against the AMIS linked companies'
 * Lao/English names and every partner name (the only names legacy contracts have).
 */
class CompanyNameRule extends TextSimilarityRule
{
    public function label(): string
    {
        return __('matching.rules.company_name');
    }

    protected function kind(): string
    {
        return MatchTermIndex::KIND_NAME;
    }

    /**
     * Many companies run the same kind of project (an iron ore plant, a coffee plantation), so a
     * matching activity alone must not make a strong suggestion when the companies clearly differ.
     */
    protected function capBelow(): ?float
    {
        return (float) config('matching.caps.company_below');
    }
}
