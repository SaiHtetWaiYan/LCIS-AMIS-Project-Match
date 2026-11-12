<?php

namespace Tests\Feature\Jobs;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Jobs\SyncLcisProjects;
use App\Models\LcisCompany;
use App\Models\LcisProject;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class SyncLcisProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_sync_stores_projects_companies_and_fallback_locations(): void
    {
        $admin = User::factory()->admin()->create();
        $this->fakeLcis(projectPages: [[$this->project(1), $this->project(2)], [$this->project(3)]], companies: [$this->fixture('lcis/company.json')]);

        SyncLcisProjects::dispatchSync($admin->id);

        $run = SyncRun::sole();
        $this->assertSame(SyncSource::Lcis, $run->source);
        $this->assertSame(SyncStatus::Succeeded, $run->status);
        $this->assertSame($admin->id, $run->triggered_by);
        $this->assertSame([3, 3, 3, 0, 0], [$run->expected_count, $run->fetched_count, $run->created_count, $run->updated_count, $run->missing_count]);
        $this->assertEquals(['created' => 1, 'updated' => 0, 'unchanged' => 0, 'restored' => 0, 'missing' => 0, 'skipped' => 0, 'fetched' => 1], $run->details['companies']);

        $project = LcisProject::with('locations')->findOrFail(2);
        $this->assertSame('AGR-2024-0012', $project->project_code);
        $this->assertSame('ບໍລິສັດ ຮ່ວມທຶນສຳພັນໄຊ ມາຍນີ້ງ ຈຳກັດ', $project->company_name);
        $this->assertSame(16, $project->responsible_province_id);
        $this->assertSame('Coffee', $project->raw['subsect_name']);
        $this->assertSame('2026-10-30 14:05:11', $project->source_updated_at->toDateTimeString());
        $this->assertNull($project->missing_since);
        $this->assertCount(1, $project->locations);
        $this->assertTrue($project->locations[0]->is_fallback);
        $this->assertSame(1601, $project->locations[0]->district_id);
        $this->assertSame('Samphanxay Mining Co., Ltd', LcisCompany::findOrFail(954)->name_eng);
    }

    public function test_resync_counts_changed_and_unchanged_projects(): void
    {
        $this->fakeLcis(projectPages: [[$this->project(1), $this->project(2)]]);
        SyncLcisProjects::dispatchSync();
        $originalHash = LcisProject::findOrFail(2)->key_fields_hash;
        $this->fakeLcis(projectPages: [[$this->project(1), $this->project(2, ['activity_name' => 'ໂຄງການປູກຢາງພາລາ'])]]);

        SyncLcisProjects::dispatchSync();

        $run = SyncRun::latest('id')->first();
        $this->assertSame([0, 1, 1], [$run->created_count, $run->updated_count, $run->unchanged_count]);
        $this->assertSame('ໂຄງການປູກຢາງພາລາ', LcisProject::findOrFail(2)->activity_name);
        $this->assertNotSame($originalHash, LcisProject::findOrFail(2)->key_fields_hash);
    }

    public function test_marks_a_project_missing_when_lcis_returns_404_and_keeps_the_row(): void
    {
        $this->freezeTime();
        $this->fakeLcis(projectPages: [[$this->project(1), $this->project(2)]]);
        SyncLcisProjects::dispatchSync();
        $this->fakeLcis(projectPages: [[$this->project(1)]]);

        SyncLcisProjects::dispatchSync();

        $run = SyncRun::latest('id')->first();
        $this->assertSame(SyncStatus::Succeeded, $run->status);
        $this->assertSame(1, $run->missing_count);
        $this->assertSame(now()->toDateTimeString(), LcisProject::findOrFail(2)->missing_since->toDateTimeString());
        $this->assertNull(LcisProject::findOrFail(1)->missing_since);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://lcis.example.test/api/v1/projects/2');
    }

    public function test_keeps_a_project_that_dropped_out_of_the_list_but_still_exists(): void
    {
        $this->fakeLcis(projectPages: [[$this->project(1), $this->project(2)]]);
        SyncLcisProjects::dispatchSync();
        $this->fakeLcis(projectPages: [[$this->project(1)]], projectDetails: [2 => $this->project(2)]);

        SyncLcisProjects::dispatchSync();

        $run = SyncRun::latest('id')->first();
        $this->assertSame([2, 0], [$run->fetched_count, $run->missing_count]);
        $this->assertNull(LcisProject::findOrFail(2)->missing_since);
    }

    public function test_clears_missing_since_when_a_missing_project_comes_back(): void
    {
        LcisProject::factory()->missing()->create(['id' => 5]);
        $this->fakeLcis(projectPages: [[$this->project(5)]]);

        SyncLcisProjects::dispatchSync();

        $this->assertNull(LcisProject::findOrFail(5)->missing_since);
        $this->assertSame(1, SyncRun::sole()->restored_count);
    }

    public function test_records_a_failed_run_when_lcis_rejects_the_token(): void
    {
        $existing = LcisProject::factory()->create();
        Http::preventStrayRequests();
        Http::fake(['lcis.example.test/*' => Http::response(['message' => 'Invalid or inactive token'], 401)]);

        SyncLcisProjects::dispatchSync();

        $run = SyncRun::sole();
        $this->assertSame(SyncStatus::Failed, $run->status);
        $this->assertSame('HTTP_401', $run->error_code);
        $this->assertSame('Invalid or inactive token', $run->error_message);
        $this->assertTrue($run->isAuthenticationFailure());
        $this->assertNotNull($run->finished_at);
        $this->assertNull($existing->refresh()->missing_since);
    }

    public function test_keeps_earlier_pages_and_marks_nothing_missing_when_a_later_page_fails(): void
    {
        Sleep::fake();
        $existing = LcisProject::factory()->create(['id' => 9]);
        $this->fakeLcis(projectPages: [[$this->project(1)], 'error']);

        SyncLcisProjects::dispatchSync();

        $run = SyncRun::sole();
        $this->assertSame(SyncStatus::Failed, $run->status);
        $this->assertSame('SERVER_ERROR', $run->error_code);
        $this->assertSame(1, $run->fetched_count);
        $this->assertModelExists(LcisProject::findOrFail(1));
        $this->assertNull($existing->refresh()->missing_since);
    }

    public function test_refuses_to_mark_many_projects_missing_when_the_list_looks_truncated(): void
    {
        LcisProject::factory()->count(30)->create();
        $this->fakeLcis(projectPages: [[$this->project(1)]]);

        SyncLcisProjects::dispatchSync();

        $run = SyncRun::sole();
        $this->assertSame(SyncStatus::Failed, $run->status);
        $this->assertSame('SUSPICIOUS_SNAPSHOT', $run->error_code);
        $this->assertSame(0, LcisProject::whereNotNull('missing_since')->count());
    }

    public function test_skips_projects_without_an_id(): void
    {
        $this->fakeLcis(projectPages: [[$this->project(1), ['project_code' => 'NO-ID']]]);

        SyncLcisProjects::dispatchSync();

        $run = SyncRun::sole();
        $this->assertSame(SyncStatus::Succeeded, $run->status);
        $this->assertSame(1, $run->details['skipped']);
        $this->assertSame(1, LcisProject::count());
    }

    public function test_closes_a_run_left_running_by_a_crashed_worker(): void
    {
        $stale = SyncRun::factory()->running()->create(['started_at' => now()->subHours(3)]);
        $this->fakeLcis(projectPages: [[$this->project(1)]]);

        SyncLcisProjects::dispatchSync();

        $stale->refresh();
        $this->assertSame(SyncStatus::Failed, $stale->status);
        $this->assertSame('STALE', $stale->error_code);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function project(int $id, array $overrides = []): array
    {
        return [...$this->fixture('lcis/project.json'), 'id' => $id, ...$overrides];
    }

    /**
     * @var array{projectPages: list<list<array<string, mixed>>|string>, companies: list<array<string, mixed>>, projectDetails: array<int, array<string, mixed>>}
     */
    private array $lcis = ['projectPages' => [], 'companies' => [], 'projectDetails' => []];

    private bool $lcisFaked = false;

    /**
     * Sets what the fake LCIS API returns; the fake is registered once, so later calls change
     * the answers for the next sync. A project page given as 'error' answers HTTP 500.
     *
     * @param  list<list<array<string, mixed>>|string>  $projectPages
     * @param  list<array<string, mixed>>  $companies
     * @param  array<int, array<string, mixed>>  $projectDetails
     */
    private function fakeLcis(array $projectPages, array $companies = [], array $projectDetails = []): void
    {
        $this->lcis = ['projectPages' => $projectPages, 'companies' => $companies, 'projectDetails' => $projectDetails];

        if ($this->lcisFaked) {
            return;
        }

        $this->lcisFaked = true;
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            ['projectPages' => $projectPages, 'companies' => $companies, 'projectDetails' => $projectDetails] = $this->lcis;
            $path = parse_url($request->url(), PHP_URL_PATH);
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);

            if ($path === '/api/v1/companies') {
                return Http::response(['data' => $companies, 'meta' => ['total' => count($companies), 'per_page' => 100, 'current_page' => 1, 'last_page' => 1]]);
            }

            if ($path === '/api/v1/projects') {
                $items = $projectPages[$page - 1] ?? [];

                return $items === 'error'
                    ? Http::response(['error' => ['code' => 'SERVER_ERROR', 'message' => 'Server error', 'status' => 500]], 500)
                    : Http::response(['data' => $items, 'meta' => [
                        'total' => array_sum(array_map(fn ($pageItems) => is_array($pageItems) ? count($pageItems) : 0, $projectPages)),
                        'per_page' => 100,
                        'current_page' => $page,
                        'last_page' => count($projectPages),
                    ]]);
            }

            if (preg_match('#^/api/v1/(projects|companies)/(\d+)$#', $path, $matches)) {
                $found = $matches[1] === 'projects' ? ($projectDetails[(int) $matches[2]] ?? null) : null;

                return $found !== null
                    ? Http::response(['data' => $found])
                    : Http::response(['error' => ['code' => 'NOT_FOUND', 'message' => 'Not found', 'status' => 404]], 404);
            }

            return Http::response('Unexpected request', 418);
        });
    }
}
