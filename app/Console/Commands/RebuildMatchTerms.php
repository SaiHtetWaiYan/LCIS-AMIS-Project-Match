<?php

namespace App\Console\Commands;

use App\Enums\SyncSource;
use App\Services\Matching\MatchTermIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Rebuilds the normalised name/activity index without a sync, e.g. after changing the
 * normalisation terms in config/matching.php.
 */
#[Signature('matching:reindex')]
#[Description('Rebuild the normalised names and activities used for match suggestions')]
class RebuildMatchTerms extends Command
{
    public function handle(MatchTermIndex $index): int
    {
        if (! $index->databaseSupportsLao()) {
            $this->components->warn(__('matching.lao_trigram_broken'));
        }

        foreach (SyncSource::cases() as $side) {
            $this->components->twoColumnDetail($side->label(), $index->rebuild($side).' texts');
        }

        return self::SUCCESS;
    }
}
