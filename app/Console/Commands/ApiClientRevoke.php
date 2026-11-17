<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deactivates an API client so its token stops working, or re-activates it with --restore.
 */
#[Signature('api-client:revoke {id : API client id} {--restore : Re-activate the client instead}')]
#[Description('Revoke (or with --restore, re-activate) an API client')]
class ApiClientRevoke extends Command
{
    public function handle(): int
    {
        $client = ApiClient::query()->find($this->argument('id'));

        if ($client === null) {
            $this->error("API client #{$this->argument('id')} not found.");

            return self::FAILURE;
        }

        $client->update(['is_active' => (bool) $this->option('restore')]);

        $this->info(sprintf('API client #%d "%s" %s.', $client->id, $client->name, $client->is_active ? 'restored' : 'revoked'));

        return self::SUCCESS;
    }
}
