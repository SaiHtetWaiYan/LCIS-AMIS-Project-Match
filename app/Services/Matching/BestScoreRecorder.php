<?php

namespace App\Services\Matching;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use Illuminate\Database\Eloquent\Builder;

/**
 * Stores each record's best score (see CandidateFinder::bestForLcisProject()) so the lists can
 * sort and filter by it. Scoring every record takes minutes, so all records are recalculated
 * after a sync (RefreshBestScores job) and only the affected ones when a pair changes.
 */
class BestScoreRecorder
{
    private const CHUNK_SIZE = 200;

    public function __construct(private readonly CandidateFinder $candidates) {}

    /**
     * @return array{lcis: int, amis: int}
     */
    public function refreshAll(): array
    {
        return [
            'lcis' => $this->refreshLcisProjects(LcisProject::query()),
            'amis' => $this->refreshAmisContracts(AmisContract::query()),
        ];
    }

    /**
     * The pair's two records, plus every record whose stored best counterpart is one of them:
     * once paired, a record is no longer suggested to others; once freed, those others are
     * recalculated with the rest after the next sync.
     */
    public function refreshAround(ProjectMatch $match): void
    {
        $this->refreshLcisProjects(LcisProject::query()->where(fn (Builder $query) => $query
            ->whereKey($match->lcis_project_id)
            ->orWhere('best_counterpart_id', $match->amis_contract_id)));

        $this->refreshAmisContracts(AmisContract::query()->where(fn (Builder $query) => $query
            ->whereKey($match->amis_contract_id)
            ->orWhere('best_counterpart_id', $match->lcis_project_id)));
    }

    /**
     * @param  Builder<LcisProject>  $query
     */
    public function refreshLcisProjects(Builder $query): int
    {
        $count = 0;

        $query->with(['locations', 'activeMatch:id,lcis_project_id,amis_contract_id,status'])
            ->chunkById(self::CHUNK_SIZE, function ($projects) use (&$count): void {
                foreach ($projects as $project) {
                    $this->store($project, $this->candidates->bestForLcisProject($project), $project->activeMatch !== null);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @param  Builder<AmisContract>  $query
     */
    public function refreshAmisContracts(Builder $query): int
    {
        $count = 0;

        $query->with(['items', 'companies:id,name_lao,name_eng', 'activeMatch:id,lcis_project_id,amis_contract_id,status'])
            ->chunkById(self::CHUNK_SIZE, function ($contracts) use (&$count): void {
                foreach ($contracts as $contract) {
                    $this->store($contract, $this->candidates->bestForAmisContract($contract), $contract->activeMatch !== null);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Written without touching updated_at: a score is derived data, not a change to the record.
     */
    private function store(LcisProject|AmisContract $record, ?Candidate $best, bool $isPair): void
    {
        $record->newQuery()->toBase()->where('id', $record->id)->update([
            'best_score' => $best?->score->total(),
            'best_counterpart_id' => $best?->record->id,
            'best_is_pair' => $isPair && $best !== null,
            'best_scored_at' => now(),
        ]);
    }
}
