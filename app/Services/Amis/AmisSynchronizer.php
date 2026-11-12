<?php

namespace App\Services\Amis;

use App\Enums\SyncSource;
use App\Exceptions\SourceApi\InvalidSourceRecordException;
use App\Models\SyncRun;
use App\Services\Amis\Data\AmisCompanyData;
use App\Services\Amis\Data\AmisContractData;
use App\Services\Matches\ReviewFlagger;
use App\Services\Matching\MatchTermIndex;
use App\Services\Sync\SnapshotTable;
use App\Services\Sync\SyncTally;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pulls all AMIS rent-land contracts (with their items) and the companies linked to them.
 * AMIS has no updated_since filter, so every sync is a full snapshot. The contract → company
 * link only exists on the companies endpoint, so the pivot is rebuilt from it each time.
 */
class AmisSynchronizer
{
    /**
     * @var array<string, int> units (as sent) that could not be converted, with how many items use them
     */
    private array $unknownUnits = [];

    /**
     * @var array<int, array<int, ?float>> shareholding_pct by contract id, by company id
     */
    private array $companyLinks = [];

    public function __construct(
        private readonly AmisApiClient $client,
        private readonly AreaUnitConverter $units,
        private readonly MatchTermIndex $matchTerms,
        private readonly ReviewFlagger $reviewFlagger,
    ) {}

    public function sync(SyncRun $run): void
    {
        $now = now();
        $run->fill(['expected_count' => $this->client->rentLandQuantity()])->save();

        $contracts = new SyncTally;
        $contractTable = new SnapshotTable('amis_contracts', SyncSource::Amis);
        $page = 1;

        do {
            $result = $this->client->rentLands($page);
            $this->writeContracts($result->items, $contractTable, $contracts, $now);
            $run->fill(['fetched_count' => $contracts->seenCount()])->save();
            $page++;
        } while (! $result->isLast());

        $contractTable->resolveUnseen(
            $contracts,
            $now,
            fn (int $id): array => $this->client->rentLand($id),
            fn (array $raw) => $this->writeContracts([$raw], $contractTable, $contracts, $now),
        );

        $companies = $this->syncCompanies($now);
        $linkCount = $this->rebuildCompanyLinks();
        $this->logUnknownUnits();

        $run->fill([
            'fetched_count' => $contracts->seenCount(),
            'created_count' => $contracts->created,
            'updated_count' => $contracts->updated,
            'unchanged_count' => $contracts->unchanged,
            'missing_count' => $contracts->missing,
            'restored_count' => $contracts->restored,
            'details' => [
                'skipped' => $contracts->skipped,
                'companies' => $companies->toArray(),
                'company_links' => $linkCount,
                'unknown_units' => $this->unknownUnits,
                'match_terms' => $this->matchTerms->rebuild(SyncSource::Amis),
                'flagged_for_review' => $this->reviewFlagger->flag(),
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function writeContracts(array $items, SnapshotTable $table, SyncTally $tally, CarbonInterface $now): void
    {
        $contracts = [];

        foreach ($items as $item) {
            try {
                $contracts[] = AmisContractData::fromApi($item, $this->units);
            } catch (InvalidSourceRecordException) {
                $tally->skipped++;
            }
        }

        if ($contracts === []) {
            return;
        }

        foreach ($contracts as $contract) {
            foreach ($contract->unknownUnits() as $unit) {
                $this->unknownUnits[$unit] = ($this->unknownUnits[$unit] ?? 0) + 1;
            }
        }

        DB::transaction(function () use ($contracts, $table, $tally, $now): void {
            $table->upsert(array_map(fn (AmisContractData $contract): array => $contract->toRow($now), $contracts), $tally);

            $contractIds = array_map(fn (AmisContractData $contract): int => $contract->id, $contracts);
            DB::table('amis_contract_items')->whereIn('amis_contract_id', $contractIds)->delete();

            $itemRows = [];

            foreach ($contracts as $contract) {
                foreach ($contract->items as $item) {
                    $itemRows[$item->id] = $item->toRow($contract->id);
                }
            }

            foreach (array_chunk(array_values($itemRows), 500) as $chunk) {
                DB::table('amis_contract_items')->upsert($chunk, ['id'], array_keys($chunk[0]));
            }
        });
    }

    private function syncCompanies(CarbonInterface $now): SyncTally
    {
        $tally = new SyncTally;
        $table = new SnapshotTable('amis_companies', SyncSource::Amis);
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
                $company = AmisCompanyData::fromApi($item);
            } catch (InvalidSourceRecordException) {
                $tally->skipped++;

                continue;
            }

            $this->companyLinks[$company->id] = $company->contractShares;
            $rows[] = $company->toRow($now);
        }

        return $rows;
    }

    /**
     * Replaces all contract ↔ company links with those from this sync, skipping links to
     * contracts we do not have.
     */
    private function rebuildCompanyLinks(): int
    {
        $knownContractIds = DB::table('amis_contracts')->pluck('id')->map(fn (mixed $id): int => (int) $id)->flip();
        $rows = [];

        foreach ($this->companyLinks as $companyId => $shares) {
            foreach ($shares as $contractId => $shareholdingPct) {
                if ($knownContractIds->has($contractId)) {
                    $rows[] = ['amis_company_id' => $companyId, 'amis_contract_id' => $contractId, 'shareholding_pct' => $shareholdingPct];
                }
            }
        }

        DB::transaction(function () use ($rows): void {
            DB::table('amis_company_contract')->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('amis_company_contract')->insert($chunk);
            }
        });

        return count($rows);
    }

    private function logUnknownUnits(): void
    {
        foreach ($this->unknownUnits as $unit => $count) {
            Log::warning('AMIS area unit not convertible to hectares; add it to config/matching.php area_units.', [
                'unit' => $unit,
                'items' => $count,
            ]);
        }
    }
}
