<?php

namespace Tests\Feature\Console;

use App\Enums\SyncStatus;
use App\Models\SyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RunSourceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_runs_an_amis_sync_and_prints_the_counts(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'amis.example.test/api/v1/public/income/rent-land/quantity' => Http::response(['data' => ['quantity' => 1]]),
            'amis.example.test/api/v1/public/income/rent-land?*' => Http::response(['rent_lands' => [$this->fixture('amis/rent_land_legacy_partner.json')], 'total' => 1, 'page' => 1, 'page_size' => 100, 'total_pages' => 1]),
            'amis.example.test/api/v1/public/companies?*' => Http::response(['companies' => [], 'total' => 0, 'page' => 1, 'page_size' => 100, 'total_pages' => 0]),
        ]);

        $this->artisan('sync:run', ['source' => 'amis'])
            ->expectsOutputToContain('Succeeded')
            ->assertSuccessful();

        $this->assertSame(SyncStatus::Succeeded, SyncRun::sole()->status);
    }

    public function test_fails_with_the_source_error_when_the_sync_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake(['amis.example.test/*' => Http::response(['message' => 'Invalid API key', 'code' => 'API_KEY_INVALID', 'detail' => ''], 401)]);

        $this->artisan('sync:run', ['source' => 'amis'])
            ->expectsOutputToContain('API_KEY_INVALID: Invalid API key')
            ->assertFailed();
    }

    public function test_rejects_an_unknown_source(): void
    {
        $this->artisan('sync:run', ['source' => 'erp'])->assertExitCode(2);

        $this->assertSame(0, SyncRun::count());
    }
}
