<?php

namespace App\Services\Matching;

use App\Enums\SyncSource;
use App\Models\AmisContract;
use App\Models\LcisProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Suggests counterpart records for an LCIS project or an AMIS contract.
 *
 * A pool is gathered cheaply — records with a similar name or activity (pg_trgm index) plus
 * records sharing a province — then every pool record is scored by the configured rules and
 * the best are returned. Records missing from their source or already in a proposed or matched
 * pair are never suggested (manual search still shows the latter, marked).
 */
class CandidateFinder
{
    public function __construct(
        private readonly TextSimilarity $textSimilarity,
        private readonly MatchScorer $scorer,
    ) {}

    /**
     * @return list<Candidate>
     */
    public function forLcisProject(LcisProject $project, ?int $limit = null): array
    {
        $project->loadMissing('locations');

        $poolIds = $this->pool(
            SyncSource::Lcis,
            $project->id,
            $project->locations->pluck('province_id')->filter()->unique()->all(),
            $project->locations->pluck('district_id')->filter()->unique()->all(),
        );

        $contracts = $this->amisQuery()->whereDoesntHave('activeMatch')->whereIn('id', $poolIds)->get();

        return $this->best($this->scoreAmisContracts($project, $contracts), $limit);
    }

    /**
     * @return list<Candidate>
     */
    public function forAmisContract(AmisContract $contract, ?int $limit = null): array
    {
        $contract->loadMissing('items');

        $poolIds = $this->pool(
            SyncSource::Amis,
            $contract->id,
            $contract->items->pluck('province_id')->filter()->unique()->all(),
            $contract->items->pluck('district_id')->filter()->unique()->all(),
        );

        $projects = $this->lcisQuery()->whereDoesntHave('activeMatch')->whereIn('id', $poolIds)->get();

        return $this->best($this->scoreLcisProjects($contract, $projects), $limit);
    }

    /**
     * The score to show for a project in a list: its proposed or matched pair if it has one,
     * otherwise its best suggestion. Expects activeMatch to be loaded with amis_contract_id.
     */
    public function bestForLcisProject(LcisProject $project): ?Candidate
    {
        $contractId = $project->activeMatch?->amis_contract_id;

        if ($contractId !== null) {
            return $this->scoreAmisContracts($project, AmisContract::query()->whereKey($contractId)->get())[0] ?? null;
        }

        return $this->forLcisProject($project, 1)[0] ?? null;
    }

    /**
     * The score to show for a contract in a list: its proposed or matched pair if it has one,
     * otherwise its best suggestion. Expects activeMatch to be loaded with lcis_project_id.
     */
    public function bestForAmisContract(AmisContract $contract): ?Candidate
    {
        $projectId = $contract->activeMatch?->lcis_project_id;

        if ($projectId !== null) {
            return $this->scoreLcisProjects($contract, LcisProject::query()->whereKey($projectId)->get())[0] ?? null;
        }

        return $this->forAmisContract($contract, 1)[0] ?? null;
    }

    /**
     * Scores given AMIS contracts (e.g. manual search results) against a project, best first.
     *
     * @param  Collection<int, AmisContract>  $contracts
     * @return list<Candidate>
     */
    public function scoreAmisContracts(LcisProject $project, Collection $contracts): array
    {
        $project->loadMissing('locations');
        $contracts->loadMissing('items');
        $textMatches = $this->textSimilarity->bestMatches(SyncSource::Lcis, $project->id, $contracts->modelKeys());

        return $this->sorted($contracts->map(fn (AmisContract $contract): Candidate => new Candidate(
            $contract,
            $this->scorer->score(new MatchPair($project, $contract, $textMatches[$contract->id] ?? [])),
        ))->all());
    }

    /**
     * Scores given LCIS projects (e.g. manual search results) against a contract, best first.
     *
     * @param  Collection<int, LcisProject>  $projects
     * @return list<Candidate>
     */
    public function scoreLcisProjects(AmisContract $contract, Collection $projects): array
    {
        $contract->loadMissing('items');
        $projects->loadMissing('locations');
        $textMatches = $this->textSimilarity->bestMatches(SyncSource::Amis, $contract->id, $projects->modelKeys());

        return $this->sorted($projects->map(fn (LcisProject $project): Candidate => new Candidate(
            $project,
            $this->scorer->score(new MatchPair($project, $contract, $textMatches[$project->id] ?? [])),
        ))->all());
    }

    /**
     * @return Builder<AmisContract>
     */
    public function amisQuery(): Builder
    {
        return AmisContract::query()->presentInSource()->with(['items', 'companies:id,name_lao,name_eng', 'activeMatch']);
    }

    /**
     * @return Builder<LcisProject>
     */
    public function lcisQuery(): Builder
    {
        return LcisProject::query()->presentInSource()->with(['locations', 'company:id,name_eng', 'activeMatch']);
    }

    /**
     * Ids on the other side worth scoring: similar texts first, then shared districts, then shared provinces.
     *
     * @param  list<int>  $provinceIds
     * @param  list<int>  $districtIds
     * @return list<int>
     */
    private function pool(SyncSource $side, int $recordId, array $provinceIds, array $districtIds): array
    {
        $otherSide = $side === SyncSource::Lcis ? SyncSource::Amis : SyncSource::Lcis;
        $poolSize = (int) config('matching.candidates.pool_size');

        $ids = DB::transaction(function () use ($side, $otherSide, $recordId, $poolSize): array {
            DB::statement('set local pg_trgm.similarity_threshold = '.(float) config('matching.candidates.text_threshold'));

            return DB::table('match_terms as s')
                ->join('match_terms as o', function ($join) use ($otherSide): void {
                    $join->on('o.kind', '=', 's.kind')->where('o.side', $otherSide->value)->whereRaw('o.text_norm % s.text_norm');
                })
                ->where('s.side', $side->value)
                ->where('s.record_id', $recordId)
                ->groupBy('o.record_id')
                ->orderByRaw('max(similarity(o.text_norm, s.text_norm)) desc')
                ->limit($poolSize)
                ->pluck('o.record_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
        });

        if ($provinceIds !== [] && count($ids) < $poolSize) {
            [$table, $foreignKey] = $otherSide === SyncSource::Amis
                ? ['amis_contract_items', 'amis_contract_id']
                : ['lcis_project_locations', 'lcis_project_id'];

            $locationIds = DB::table($table)
                ->whereIn('province_id', $provinceIds)
                ->whereNotIn($foreignKey, $ids === [] ? [0] : $ids)
                ->groupBy($foreignKey)
                ->orderByRaw('coalesce(bool_or(district_id = any(?::int[])), false) desc', ['{'.implode(',', array_map('intval', $districtIds)).'}'])
                ->orderBy($foreignKey)
                ->limit($poolSize - count($ids))
                ->pluck($foreignKey)
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $ids = [...$ids, ...$locationIds];
        }

        return $ids;
    }

    /**
     * @param  list<Candidate>  $candidates
     * @return list<Candidate>
     */
    private function best(array $candidates, ?int $limit): array
    {
        return array_slice($candidates, 0, $limit ?? (int) config('matching.candidates.limit'));
    }

    /**
     * Highest total first; ties go to the candidate backed by more rules.
     *
     * @param  list<Candidate>  $candidates
     * @return list<Candidate>
     */
    private function sorted(array $candidates): array
    {
        usort($candidates, fn (Candidate $a, Candidate $b): int => [$b->score->total() ?? -1, $b->score->rulesWithData()] <=> [$a->score->total() ?? -1, $a->score->rulesWithData()]);

        return $candidates;
    }
}
