<?php

namespace Tests\Feature\Admin;

use App\Enums\UserSide;
use App\Jobs\SyncAmisContracts;
use App\Jobs\SyncLcisProjects;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SyncControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{UserSide}>
     */
    public static function nonAdminSides(): array
    {
        return [
            'lcis' => [UserSide::Lcis],
            'amis' => [UserSide::Amis],
        ];
    }

    #[DataProvider('nonAdminSides')]
    public function test_forbids_non_admin_users_from_the_sync_page(UserSide $side): void
    {
        $this->actingAs(User::factory()->create(['side' => $side]))->get('/admin/sync')->assertForbidden();
    }

    public function test_shows_the_latest_run_per_source_with_counts(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Sync Admin']);
        SyncRun::factory()->create(['triggered_by' => $admin->id, 'fetched_count' => 1234, 'expected_count' => 1234]);
        SyncRun::factory()->amis()->create(['details' => ['unknown_units' => ['ໄລ່' => 3], 'company_links' => 12, 'companies' => ['fetched' => 5, 'created' => 1, 'updated' => 0, 'unchanged' => 4, 'missing' => 0, 'restored' => 0, 'skipped' => 0]]]);

        $response = $this->actingAs($admin)->get('/admin/sync');

        $response->assertOk();
        $response->assertSeeText(['1234 / 1234', 'Sync Admin', 'Scheduler / command line', 'ໄລ່', '12 contract ↔ company links.']);
    }

    public function test_explains_an_amis_api_key_rejection(): void
    {
        $admin = User::factory()->admin()->create();
        SyncRun::factory()->amis()->failed('API_KEY_INVALID', 'Invalid API key')->create();

        $response = $this->actingAs($admin)->get('/admin/sync');

        $response->assertOk();
        $response->assertSeeText(['API key invalid (or not allowed from this domain)', 'API_KEY_INVALID', 'Invalid API key', 'Check AMIS_API_KEY in .env']);
    }

    public function test_admin_queues_a_sync_and_records_who_triggered_it(): void
    {
        Queue::fake([SyncLcisProjects::class, SyncAmisContracts::class]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->from('/admin/sync')->post('/admin/sync/amis');

        $response->assertRedirect('/admin/sync');
        $response->assertSessionHas('status', 'AMIS sync queued. It runs on the queue worker; refresh this page to follow it.');
        Queue::assertPushed(SyncAmisContracts::class, fn (SyncAmisContracts $job): bool => $job->triggeredBy === $admin->id);
        Queue::assertNotPushed(SyncLcisProjects::class);
    }

    public function test_does_not_queue_a_second_sync_while_one_is_running(): void
    {
        Queue::fake([SyncLcisProjects::class]);
        $admin = User::factory()->admin()->create();
        SyncRun::factory()->running()->create();

        $response = $this->actingAs($admin)->post('/admin/sync/lcis');

        $response->assertSessionHas('error', 'A LCIS sync is already running.');
        Queue::assertNotPushed(SyncLcisProjects::class);
    }

    public function test_rejects_an_unknown_source(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/sync/erp')->assertNotFound();
    }

    public function test_forbids_non_admin_users_from_triggering_a_sync(): void
    {
        Queue::fake([SyncLcisProjects::class]);

        $this->actingAs(User::factory()->lcis()->create())->post('/admin/sync/lcis')->assertForbidden();

        Queue::assertNotPushed(SyncLcisProjects::class);
    }
}
