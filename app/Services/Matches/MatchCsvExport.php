<?php

namespace App\Services\Matches;

use App\Models\ProjectMatch;
use Illuminate\Database\Eloquent\Builder;

/**
 * Writes matches as CSV, one row per match, with both records' identifiers and the whole
 * confirmation trail. Starts with a UTF-8 byte order mark so Excel shows Lao text correctly.
 */
class MatchCsvExport
{
    /**
     * @param  Builder<ProjectMatch>  $matches
     * @param  resource  $output
     */
    public function write(Builder $matches, $output): void
    {
        fwrite($output, "\u{FEFF}");
        fputcsv($output, $this->headings(), escape: '');

        $matches
            ->with([
                'lcisProject:id,project_code,activity_name,company_name',
                'amisContract:id,contract_number,contract_name,partner_names',
                'proposer:id,name', 'lcisConfirmer:id,name', 'amisConfirmer:id,name',
            ])
            ->lazyById(500)
            ->each(fn (ProjectMatch $match) => fputcsv($output, $this->row($match), escape: ''));
    }

    /**
     * @return list<string>
     */
    private function headings(): array
    {
        return array_map(fn (string $column): string => __('matches.csv.'.$column), [
            'match_id', 'status', 'needs_review',
            'lcis_project_id', 'lcis_project_code', 'lcis_activity', 'lcis_company',
            'amis_contract_id', 'amis_contract_number', 'amis_contract_name', 'amis_partners',
            'proposed_by', 'proposed_at', 'lcis_confirmed_by', 'lcis_confirmed_at', 'amis_confirmed_by', 'amis_confirmed_at',
            'matched_at', 'rejected_at', 'rejection_reason', 'unmatched_at', 'unmatch_reason',
        ]);
    }

    /**
     * @return list<string|int|null>
     */
    private function row(ProjectMatch $match): array
    {
        return [
            $match->id,
            $match->status->value,
            $match->needs_review ? 'yes' : 'no',
            $match->lcis_project_id,
            $match->lcisProject?->project_code,
            $match->lcisProject?->activity_name,
            $match->lcisProject?->company_name,
            $match->amis_contract_id,
            $match->amisContract?->contract_number,
            $match->amisContract?->contract_name,
            implode('; ', $match->amisContract?->partner_names ?? []),
            $match->proposer?->name,
            $match->created_at?->toDateTimeString(),
            $match->lcisConfirmer?->name,
            $match->lcis_confirmed_at?->toDateTimeString(),
            $match->amisConfirmer?->name,
            $match->amis_confirmed_at?->toDateTimeString(),
            $match->matched_at?->toDateTimeString(),
            $match->rejected_at?->toDateTimeString(),
            $match->rejection_reason,
            $match->unmatched_at?->toDateTimeString(),
            $match->unmatch_reason,
        ];
    }
}
