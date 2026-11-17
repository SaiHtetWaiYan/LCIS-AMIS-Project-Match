<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Registers an external system that may call /api/v1. The plain token is printed once; only its hash is stored.
 */
#[Signature('api-client:create {name : Client name, e.g. AMIS}')]
#[Description('Create an API client and print its bearer token')]
class ApiClientCreate extends Command
{
    public function handle(): int
    {
        $plainToken = ApiClient::generateToken();

        $client = ApiClient::create([
            'name' => $this->argument('name'),
            'token_hash' => ApiClient::hashToken($plainToken),
            'is_active' => true,
        ]);

        $this->info("API client #{$client->id} \"{$client->name}\" created.");
        $this->line('Bearer token (shown only once, store it securely):');
        $this->line($plainToken);

        return self::SUCCESS;
    }
}
