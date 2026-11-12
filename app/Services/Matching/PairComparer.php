<?php

namespace App\Services\Matching;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Services\ProvinceDirectory;

/**
 * Builds the compare screen for an LCIS project and an AMIS contract: the configured rules'
 * scores and values first, then the other key fields, flagging differences only where both
 * systems record the same thing (sector and administering province).
 */
class PairComparer
{
    public function __construct(
        private readonly CandidateFinder $candidates,
        private readonly MatchScorer $scorer,
        private readonly ProvinceDirectory $provinces,
    ) {}

    public function compare(LcisProject $project, AmisContract $contract): PairComparison
    {
        $project->loadMissing(['locations', 'company']);
        $contract->loadMissing(['items', 'companies']);

        return new PairComparison(
            $project,
            $contract,
            $this->candidates->scoreAmisContracts($project, $contract->newCollection([$contract]))[0]->score,
            $this->scorer->values(new MatchPair($project, $contract, [])),
            $this->details($project, $contract),
        );
    }

    /**
     * @return list<ComparedDetail>
     */
    private function details(LcisProject $project, AmisContract $contract): array
    {
        $budgetProvince = $contract->from_province_id !== null ? $this->provinces->name($contract->from_province_id) : null;

        return [
            new ComparedDetail(
                __('compare.details.sector'),
                $project->project_type,
                $contract->business_type_name,
                self::bothKnown($project->project_type, $contract->business_type_name)
                    ? self::normalize($project->project_type) !== self::normalize($contract->business_type_name)
                    : null,
            ),
            new ComparedDetail(
                __('compare.details.administering_province'),
                self::join([$project->responsible_province_name, $project->responsible_district_name]),
                $budgetProvince,
                $project->responsible_province_id !== null && $contract->from_province_id !== null
                    ? $project->responsible_province_id !== $contract->from_province_id
                    : null,
            ),
            new ComparedDetail(
                __('compare.details.kind'),
                $project->subsector_name,
                $contract->contract_type ? __('amis.contract_types.'.$contract->contract_type) : null,
            ),
            new ComparedDetail(
                __('compare.details.status'),
                $project->negotiation_status_name,
                $contract->status ? __('amis.statuses.'.$contract->status) : null,
            ),
            new ComparedDetail(
                __('compare.details.period'),
                null,
                self::join([$contract->starts_on?->toDateString(), $contract->ends_on?->toDateString()], ' → '),
            ),
            new ComparedDetail(
                __('compare.details.reference'),
                self::join([$project->project_code, __('compare.details.id', ['id' => $project->id])]),
                self::join([$contract->contract_number, __('compare.details.id', ['id' => $contract->id])]),
            ),
            new ComparedDetail(
                __('compare.details.updated_at'),
                $project->source_updated_at?->toDateTimeString(),
                $contract->source_updated_at?->toDateTimeString(),
            ),
        ];
    }

    private static function bothKnown(?string $a, ?string $b): bool
    {
        return trim((string) $a) !== '' && trim((string) $b) !== '';
    }

    private static function normalize(?string $value): string
    {
        return mb_strtolower((string) preg_replace('/[\s\x{200B}-\x{200D}\x{FEFF}]+/u', '', (string) $value));
    }

    /**
     * @param  list<?string>  $parts
     */
    private static function join(array $parts, string $glue = ' · '): ?string
    {
        $joined = collect($parts)->filter(fn (?string $part): bool => trim((string) $part) !== '')->implode($glue);

        return $joined === '' ? null : $joined;
    }
}
