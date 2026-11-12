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
}
