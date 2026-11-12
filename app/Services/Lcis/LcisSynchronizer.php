<?php

namespace App\Services\Lcis;

use App\Enums\SyncSource;
use App\Exceptions\SourceApi\InvalidSourceRecordException;
use App\Models\SyncRun;
use App\Services\Lcis\Data\LcisCompanyData;
use App\Services\Lcis\Data\LcisProjectData;
use App\Services\Matches\ReviewFlagger;
use App\Services\Matching\MatchTermIndex;
use App\Services\Sync\SnapshotTable;
use App\Services\Sync\SyncTally;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Pulls the full LCIS company and project lists into lcis_companies, lcis_projects and
 * lcis_project_locations. LCIS has no updated_since filter, so every sync is a full snapshot.
 */
class LcisSynchronizer
{
    public function __construct(
        private readonly LcisApiClient $client,
        private readonly MatchTermIndex $matchTerms,
        private readonly ReviewFlagger $reviewFlagger,
    ) {}

    public function sync(SyncRun $run): void
    {
        $now = now();
        $companies = $this->syncCompanies($now);

        $projects = new SyncTally;
        $projectTable = new SnapshotTable('lcis_projects', SyncSource::Lcis);
        $page = 1;

        do {
            $result = $this->client->projects($page);
            $run->expected_count ??= $result->total;
            $this->writeProjects($result->items, $projectTable, $projects, $now);
            $run->fill(['fetched_count' => $projects->seenCount()])->save();
            $page++;
        } while (! $result->isLast());

        $projectTable->resolveUnseen(
            $projects,
            $now,
            fn (int $id): array => $this->client->project($id),
            fn (array $raw) => $this->writeProjects([$raw], $projectTable, $projects, $now),
        );

        $run->fill([
            'fetched_count' => $projects->seenCount(),
            'created_count' => $projects->created,
            'updated_count' => $projects->updated,
            'unchanged_count' => $projects->unchanged,
            'missing_count' => $projects->missing,
            'restored_count' => $projects->restored,
            'details' => [
                'skipped' => $projects->skipped,
                'companies' => $companies->toArray(),
                'match_terms' => $this->matchTerms->rebuild(SyncSource::Lcis),
                'flagged_for_review' => $this->reviewFlagger->flag(),
            ],
        ]);
    }

    private function syncCompanies(CarbonInterface $now): SyncTally
    {
        $tally = new SyncTally;
        $table = new SnapshotTable('lcis_companies', SyncSource::Lcis);
        $page = 1;

        do {
            $result = $this->client->companies($page);
            $table->upsert($this->companyRows($result->items, $tally, $now), $tally);
            $page++;
        } while (! $result->isLast());

        $table->resolveUnseen(
            $tally,
            $now,
            fn (int $id): array => $this->client->company($id),
            fn (array $raw) => $table->upsert($this->companyRows([$raw], $tally, $now), $tally),
        );

        return $tally;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function companyRows(array $items, SyncTally $tally, CarbonInterface $now): array
    {
        $rows = [];

        foreach ($items as $item) {
            try {
                $rows[] = LcisCompanyData::fromApi($item)->toRow($now);
            } catch (InvalidSourceRecordException) {
                $tally->skipped++;
            }
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function writeProjects(array $items, SnapshotTable $table, SyncTally $tally, CarbonInterface $now): void
    {
        $projects = [];

        foreach ($items as $item) {
            try {
                $projects[] = LcisProjectData::fromApi($item);
            } catch (InvalidSourceRecordException) {
                $tally->skipped++;
            }
        }

        if ($projects === []) {
            return;
        }

        DB::transaction(function () use ($projects, $table, $tally, $now): void {
            $table->upsert(array_map(fn (LcisProjectData $project): array => $project->toRow($now), $projects), $tally);

            $projectIds = array_map(fn (LcisProjectData $project): int => $project->id, $projects);
            DB::table('lcis_project_locations')->whereIn('lcis_project_id', $projectIds)->delete();

            $locationRows = [];

            foreach ($projects as $project) {
                foreach ($project->locations as $location) {
                    $locationRows[] = $location->toRow($project->id);
                }
            }

            foreach (array_chunk($locationRows, 500) as $chunk) {
                DB::table('lcis_project_locations')->insert($chunk);
            }
        });
    }
}
