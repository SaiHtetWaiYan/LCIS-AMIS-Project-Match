<?php

namespace Tests\Feature\Api;

use App\Enums\MatchStatus;
use App\Models\AmisContract;
use App\Models\ApiClient;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-token-0123456789-0123456789-0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        ApiClient::factory()->withToken(self::TOKEN)->create(['name' => 'AMIS']);
    }

    public function test_lists_only_confirmed_matches_with_both_records(): void
    {
        $project = LcisProject::factory()->create(['project_code' => '03706.01', 'activity_name' => 'ເຂື່ອນໄຟຟ້ານໍ້າໂມ້ 2', 'company_name' => 'ບໍລິສັດ ໄຟຟ້ານໍ້າໂມ້ 2 ຈຳກັດ']);
        $contract = AmisContract::factory()->create(['contract_number' => '415/ພຊສ.ຈສ', 'contract_type' => 'concession', 'contract_name' => 'ໂຄງການເຂື່ອນໄຟຟ້ານ້ຳໂມ້ 2']);
        $match = ProjectMatch::factory()->matched()->needsReview()->create([
            'lcis_project_id' => $project->id,
            'amis_contract_id' => $contract->id,
            'matched_at' => '2026-11-16 06:25:17',
        ]);
        ProjectMatch::factory()->create();
        ProjectMatch::factory()->rejected()->create();
        ProjectMatch::factory()->create(['status' => MatchStatus::Unmatched]);

        $this->withToken(self::TOKEN)->getJson('/api/v1/matches')
            ->assertOk()
            ->assertExactJson([
                'data' => [[
                    'id' => $match->id,
                    'lcis' => ['project_id' => $project->id, 'project_code' => '03706.01', 'activity_name' => 'ເຂື່ອນໄຟຟ້ານໍ້າໂມ້ 2', 'company_name' => 'ບໍລິສັດ ໄຟຟ້ານໍ້າໂມ້ 2 ຈຳກັດ'],
                    'amis' => ['contract_id' => $contract->id, 'contract_number' => '415/ພຊສ.ຈສ', 'contract_type' => 'concession', 'contract_name' => 'ໂຄງການເຂື່ອນໄຟຟ້ານ້ຳໂມ້ 2'],
                    'matched_at' => '2026-11-16 06:25:17',
                    'needs_review' => true,
                ]],
                'meta' => ['total' => 1, 'per_page' => 100, 'current_page' => 1, 'last_page' => 1],
            ]);
    }

    public function test_lists_the_newest_match_first_and_paginates(): void
    {
        $older = ProjectMatch::factory()->matched()->create(['matched_at' => '2026-11-12 08:00:00']);
        $newer = ProjectMatch::factory()->matched()->create(['matched_at' => '2026-11-16 08:00:00']);

        $this->withToken(self::TOKEN)->getJson('/api/v1/matches?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('meta', ['total' => 2, 'per_page' => 1, 'current_page' => 2, 'last_page' => 2]);

        $this->withToken(self::TOKEN)->getJson('/api/v1/matches')->assertJsonPath('data.0.id', $newer->id);
    }

    public function test_filters_by_lcis_project_or_amis_contract(): void
    {
        $match = ProjectMatch::factory()->matched()->create();
        ProjectMatch::factory()->matched()->create();

        $this->withToken(self::TOKEN)->getJson("/api/v1/matches?lcis_project_id={$match->lcis_project_id}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);

        $this->withToken(self::TOKEN)->getJson("/api/v1/matches?amis_contract_id={$match->amis_contract_id}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_rejects_invalid_filters_with_the_error_envelope(): void
    {
        $this->withToken(self::TOKEN)->getJson('/api/v1/matches?per_page=501')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonPath('error.status', 422)
            ->assertJsonStructure(['error' => ['fields' => ['per_page']]]);
    }

    public function test_requires_the_token_of_an_active_client(): void
    {
        ApiClient::factory()->withToken('revoked-token-0123456789-0123456789-0123')->revoked()->create();

        $this->getJson('/api/v1/matches')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHORIZED');

        $this->withToken('unknown-token')->getJson('/api/v1/matches')->assertUnauthorized();
        $this->withToken('revoked-token-0123456789-0123456789-0123')->getJson('/api/v1/matches')->assertUnauthorized();
    }

    public function test_records_when_a_client_last_used_its_token(): void
    {
        $this->withToken(self::TOKEN)->getJson('/api/v1/matches')->assertOk();

        $this->assertNotNull(ApiClient::sole()->last_used_at);
    }

    public function test_ping_needs_no_token(): void
    {
        $this->getJson('/api/v1/ping')->assertOk()->assertExactJson(['status' => 'ok']);
    }
}
