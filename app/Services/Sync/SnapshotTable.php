<?php

namespace App\Services\Sync;

use App\Enums\SyncSource;
use App\Exceptions\SourceApi\SourceNotFoundException;
use App\Exceptions\SourceApi\SourceResponseException;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * A local table holding a full snapshot of a source list, keyed by the source id.
 * Rows are upserted, never deleted; a row the source no longer has gets missing_since.
 */
final class SnapshotTable
{
    /**
     * Above this share of unseen rows the snapshot is treated as broken rather than as deletions.
     */
    private const MAX_UNSEEN_SHARE = 0.2;

    /**
     * Below this many unseen rows the share check does not apply.
     */
    private const MIN_UNSEEN_FOR_SHARE_CHECK = 25;

    public function __construct(
        private readonly string $table,
        private readonly SyncSource $source,
    ) {}

    /**
     * Upserts rows (each with id and payload_hash) and counts created/updated/unchanged/restored.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsert(array $rows, SyncTally $tally): void
    {
        if ($rows === []) {
            return;
        }

        $existing = DB::table($this->table)
            ->whereIn('id', array_column($rows, 'id'))
            ->get(['id', 'payload_hash', 'missing_since'])
            ->keyBy('id');

        foreach ($rows as $row) {
            $current = $existing->get($row['id']);

            match (true) {
                $current === null => $tally->created++,
                $current->payload_hash !== $row['payload_hash'] => $tally->updated++,
                default => $tally->unchanged++,
            };

            if ($current?->missing_since !== null) {
                $tally->restored++;
            }

            $tally->markSeen($row['id']);
        }

        DB::table($this->table)->upsert($rows, ['id'], array_values(array_diff(array_keys($rows[0]), ['id', 'created_at'])));
    }

    /**
     * After a complete pass over the source list: looks up every active row the list did not
     * return. A 404 marks it missing; a record that is found is handed to $restore.
     *
     * @param  Closure(int): array<string, mixed>  $fetchOne  throws SourceNotFoundException when gone
     * @param  Closure(array<string, mixed>): void  $restore
     */
    public function resolveUnseen(SyncTally $tally, CarbonInterface $now, Closure $fetchOne, Closure $restore): void
    {
        $activeIds = DB::table($this->table)->whereNull('missing_since')->pluck('id')->map(fn (mixed $id): int => (int) $id);
        $unseenIds = $activeIds->reject(fn (int $id): bool => $tally->hasSeen($id))->values();

        if ($unseenIds->count() >= self::MIN_UNSEEN_FOR_SHARE_CHECK && $unseenIds->count() > $activeIds->count() * self::MAX_UNSEEN_SHARE) {
            throw new SourceResponseException(
                $this->source,
                'SUSPICIOUS_SNAPSHOT',
                "{$this->source->label()} returned no {$this->table} record for {$unseenIds->count()} of {$activeIds->count()} known rows. Nothing was marked missing; check the source before the next sync.",
            );
        }

        $missingIds = [];

        foreach ($unseenIds as $id) {
            try {
                $restore($fetchOne($id));
            } catch (SourceNotFoundException) {
                $missingIds[] = $id;
            }
        }

        foreach (array_chunk($missingIds, 500) as $chunk) {
            DB::table($this->table)->whereIn('id', $chunk)->whereNull('missing_since')->update(['missing_since' => $now]);
        }

        $tally->missing += count($missingIds);
    }
}
