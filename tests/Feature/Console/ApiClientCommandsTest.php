<?php

namespace Tests\Feature\Console;

use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ApiClientCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_prints_a_token_that_is_stored_only_as_a_hash(): void
    {
        Artisan::call('api-client:create', ['name' => 'AMIS']);
        $token = trim(collect(explode("\n", trim(Artisan::output())))->last());

        $client = ApiClient::sole();
        $this->assertSame('AMIS', $client->name);
        $this->assertSame(80, strlen($token));
        $this->assertSame(ApiClient::hashToken($token), $client->token_hash);
        $this->assertTrue(ApiClient::findActiveByToken($token)->is($client));
    }

    public function test_revoke_deactivates_and_restore_reactivates_a_client(): void
    {
        $client = ApiClient::factory()->create(['name' => 'AMIS']);

        $this->artisan('api-client:revoke', ['id' => $client->id])
            ->expectsOutputToContain('"AMIS" revoked')
            ->assertSuccessful();
        $this->assertFalse($client->fresh()->is_active);

        $this->artisan('api-client:revoke', ['id' => $client->id, '--restore' => true])
            ->expectsOutputToContain('"AMIS" restored')
            ->assertSuccessful();
        $this->assertTrue($client->fresh()->is_active);
    }

    public function test_revoke_fails_for_an_unknown_client(): void
    {
        $this->artisan('api-client:revoke', ['id' => 999])
            ->expectsOutputToContain('not found')
            ->assertFailed();
    }
}
