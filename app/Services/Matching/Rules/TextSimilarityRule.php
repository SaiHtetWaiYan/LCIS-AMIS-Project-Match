<?php

namespace App\Services\Matching\Rules;

use App\Services\Matching\MatchPair;
use App\Services\Matching\MatchRule;
use App\Services\Matching\MatchTermIndex;
use App\Services\Matching\RuleResult;
use App\Services\Matching\RuleValues;
use Illuminate\Support\Facades\Lang;

/**
 * Scores the most similar pair of normalised texts of one kind (pg_trgm similarity, 0–1) and
 * names which pair it was.
 */
abstract class TextSimilarityRule implements MatchRule
{
    public function __construct(private readonly MatchTermIndex $terms) {}

    abstract protected function kind(): string;

    public function evaluate(MatchPair $pair): ?RuleResult
    {
        $match = $pair->bestTextMatch($this->kind());

        if ($match === null) {
            return null;
        }

        return new RuleResult($match->score, __('matching.reasons.text', [
            'lcis_source' => __('matching.sources.'.$match->lcisSource),
            'lcis_text' => $match->lcisText,
            'amis_source' => __('matching.sources.'.$match->amisSource),
            'amis_text' => $match->amisText,
        ]));
    }

    public function values(MatchPair $pair): RuleValues
    {
        return new RuleValues(
            $this->textsOfKind($this->terms->lcisTexts($pair->project)),
            $this->textsOfKind($this->terms->amisTexts($pair->contract)),
        );
    }

    /**
     * @param  list<array{string, string, ?string}>  $texts  [kind, source, text] rows
     * @return list<array{text: string, note: ?string}>
     */
    private function textsOfKind(array $texts): array
    {
        $ofKind = array_filter($texts, fn (array $text): bool => $text[0] === $this->kind());

        return RuleValues::list(array_map(fn (array $text): array => [$text[2], Lang::has('matching.value_notes.'.$text[1]) ? __('matching.value_notes.'.$text[1]) : null], $ofKind));
    }
}
