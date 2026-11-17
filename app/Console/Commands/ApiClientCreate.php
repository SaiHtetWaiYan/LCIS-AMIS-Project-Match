<?php

namespace App\Console\Commands;

use App\Enums\UserSide;
use App\Models\ApiClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Registers an external system that may call /api/v1. Users of its side (and admins) see the token
 * in the guide; it is stored encrypted, and requests are checked against its SHA-256 hash.
 */
#[Signature('api-client:create {side : lcis or amis} {--name= : Client name (defaults to LCIS or AMIS)}')]
#[Description('Create an API client for the LCIS or AMIS side and print its bearer token')]
class ApiClientCreate extends Command
{
    public function handle(): int
    {
        $side = UserSide::tryFrom(strtolower((string) $this->argument('side')));

        if (! in_array($side, [UserSide::Lcis, UserSide::Amis], true)) {
            $this->error('Side must be "lcis" or "amis".');

            return self::INVALID;
        }

        $plainToken = ApiClient::generateToken();

        $client = ApiClient::create([
            'name' => $this->option('name') ?: strtoupper($side->value),
            'side' => $side,
            'token_hash' => ApiClient::hashToken($plainToken),
            'token' => $plainToken,
            'is_active' => true,
        ]);

        $this->info("API client #{$client->id} \"{$client->name}\" created. {$side->label()} users see its token in the guide.");
        $this->line('Bearer token:');
        $this->line($plainToken);

        return self::SUCCESS;
    }
}
