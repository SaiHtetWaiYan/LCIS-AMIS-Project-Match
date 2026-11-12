<?php

namespace App\Services\Matching;

use App\Enums\SyncSource;
use Illuminate\Support\Facades\DB;

/**
 * Fetches, in one query, the best pg_trgm similarity per kind between one record and many
 * records of the other side, using the normalised texts in match_terms.
 */
class TextSimilarity
{
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
                    o.source as other_source, o.text as other_text, similarity(s.text_norm, o.text_norm) as score
             from match_terms s
             join match_terms o on o.side = ? and o.kind = s.kind and o.record_id = any(?::bigint[])
             where s.side = ? and s.record_id = ?',
            [$otherSide->value, '{'.implode(',', array_map('intval', $otherIds)).'}', $side->value, $recordId],
        );

        $best = [];

        foreach ($rows as $row) {
            $current = $best[$row->other_id][$row->kind] ?? null;

            if ($current !== null && $current->score >= (float) $row->score) {
                continue;
            }

            $best[(int) $row->other_id][$row->kind] = $side === SyncSource::Lcis
                ? new TextMatch((float) $row->score, $row->self_source, $row->self_text, $row->other_source, $row->other_text)
                : new TextMatch((float) $row->score, $row->other_source, $row->other_text, $row->self_source, $row->self_text);
        }

        return $best;
    }
}
