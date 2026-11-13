<?php

namespace App\Services\Matching;

use App\Enums\SyncSource;
use Illuminate\Support\Facades\DB;

/**
 * Fetches, in one query, the best pg_trgm similarity per kind between one record and many
 * records of the other side, using the normalised texts in match_terms.
 *
 * A pair of texts whose project numbers disagree (ນ້ຳກົງ 1 vs ນ້ຳກົງ 3) has its similarity
 * reduced by matching.number_mismatch_factor before the best pair is chosen.
 */
class TextSimilarity
{
    public function __construct(private readonly TextNormalizer $normalizer) {}

    /**
     * @param  list<int>  $otherIds
     * @return array<int, array<string, TextMatch>> best match by kind, by other record id
     */
    public function bestMatches(SyncSource $side, int $recordId, array $otherIds): array
    {
        if ($otherIds === []) {
            return [];
        }

        $otherSide = $side === SyncSource::Lcis ? SyncSource::Amis : SyncSource::Lcis;

        $rows = DB::select(
            'select o.record_id as other_id, s.kind, s.source as self_source, s.text as self_text,
                    o.source as other_source, o.text as other_text, s.text_norm as self_norm, o.text_norm as other_norm,
                    similarity(s.text_norm, o.text_norm) as score
             from match_terms s
             join match_terms o on o.side = ? and o.kind = s.kind and o.record_id = any(?::bigint[])
             where s.side = ? and s.record_id = ?',
            [$otherSide->value, '{'.implode(',', array_map('intval', $otherIds)).'}', $side->value, $recordId],
        );

        $best = [];
        $mismatchFactor = (float) config('matching.number_mismatch_factor');

        foreach ($rows as $row) {
            $numbersDiffer = $this->normalizer->numbersConflict($row->self_norm, $row->other_norm);
            $score = (float) $row->score * ($numbersDiffer ? $mismatchFactor : 1.0);
            $current = $best[$row->other_id][$row->kind] ?? null;

            if ($current !== null && $current->score >= $score) {
                continue;
            }

            $best[(int) $row->other_id][$row->kind] = $side === SyncSource::Lcis
                ? new TextMatch($score, $row->self_source, $row->self_text, $row->other_source, $row->other_text, $numbersDiffer, $row->self_norm, $row->other_norm)
                : new TextMatch($score, $row->other_source, $row->other_text, $row->self_source, $row->self_text, $numbersDiffer, $row->other_norm, $row->self_norm);
        }

        return $best;
    }
}
