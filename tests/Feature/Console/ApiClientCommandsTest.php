<?php

namespace Tests\Feature\Console;

use App\Enums\UserSide;
use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ApiClientCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_prints_a_token_for_the_side_and_stores_it_encrypted(): void
    {
        Artisan::call('api-client:create', ['side' => 'amis']);
        $token = trim(collect(explode("\n", trim(Artisan::output())))->last());

        $client = ApiClient::sole();
        $this->assertSame('AMIS', $client->name);
        $this->assertSame(UserSide::Amis, $client->side);
        $this->assertSame(80, strlen($token));
        $this->assertSame($token, $client->token);
        $this->assertNotSame($token, $client->getRawOriginal('token'));
        $this->assertTrue(ApiClient::findActiveByToken($token)->is($client));
    }

    public function test_create_rejects_a_side_that_is_not_lcis_or_amis(): void
    {
        $this->artisan('api-client:create', ['side' => 'admin'])
            ->expectsOutputToContain('Side must be "lcis" or "amis".')
            ->assertExitCode(2);

        $this->assertSame(0, ApiClient::count());
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
