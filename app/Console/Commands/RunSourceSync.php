<?php

namespace App\Console\Commands;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Jobs\SyncAmisContracts;
use App\Jobs\SyncLcisProjects;
use App\Models\SyncRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs a sync in this process (no queue worker needed), e.g. for the first sync after deploying.
 */
#[Signature('sync:run {source : lcis or amis}')]
#[Description('Run a full sync of LCIS projects or AMIS contracts now and print the result')]
class RunSourceSync extends Command
{
    public function handle(): int
    {
        $source = SyncSource::tryFrom((string) $this->argument('source'));

        if ($source === null) {
            $this->components->error('Source must be "lcis" or "amis".');

            return self::INVALID;
        }

        $this->components->info("Syncing {$source->label()}…");

        match ($source) {
            SyncSource::Lcis => SyncLcisProjects::dispatchSync(),
            SyncSource::Amis => SyncAmisContracts::dispatchSync(),
        };

        $run = SyncRun::query()->forSource($source)->latest('id')->firstOrFail();

        $this->components->twoColumnDetail('Status', $run->status->label());
        $this->components->twoColumnDetail('Fetched / expected', "{$run->fetched_count} / ".($run->expected_count ?? '?'));
        $this->components->twoColumnDetail('Created / updated / unchanged', "{$run->created_count} / {$run->updated_count} / {$run->unchanged_count}");
        $this->components->twoColumnDetail('Missing / restored', "{$run->missing_count} / {$run->restored_count}");

        if ($run->status === SyncStatus::Failed) {
            $this->components->error("{$run->error_code}: {$run->error_message}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
