<?php

namespace App\Services\Sync;

/**
 * Running counts for one table during a sync, plus the ids the source returned.
 */
final class SyncTally
{
    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $restored = 0;

    public int $missing = 0;

    public int $skipped = 0;

    /**
     * @var array<int, true>
     */
    private array $seenIds = [];

    public function markSeen(int $id): void
    {
        $this->seenIds[$id] = true;
    }

    public function hasSeen(int $id): bool
    {
        return isset($this->seenIds[$id]);
    }

    public function seenCount(): int
    {
        return count($this->seenIds);
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, restored: int, missing: int, skipped: int, fetched: int}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'restored' => $this->restored,
            'missing' => $this->missing,
            'skipped' => $this->skipped,
            'fetched' => $this->seenCount(),
        ];
    }
}
