<?php

namespace Tests\Feature;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMatchListTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_matches_with_both_records(): void
    {
        ProjectMatch::factory()->create([
            'lcis_project_id' => LcisProject::factory()->create(['project_code' => 'AGR-0012']),
            'amis_contract_id' => AmisContract::factory()->create(['contract_number' => '5871/MTC']),
        ]);

        $response = $this->actingAs(User::factory()->amis()->create())->get('/matches');

        $response->assertOk();
        $response->assertSeeTextInOrder(['AGR-0012', '5871/MTC', 'Proposed']);
    }

    public function test_filters_by_status_and_review_flag(): void
    {
        $proposed = ProjectMatch::factory()->create();
        $matched = ProjectMatch::factory()->matched()->create();
        $flagged = ProjectMatch::factory()->matched()->needsReview()->create();
        $user = User::factory()->lcis()->create();

        $this->actingAs($user)->get('/matches?status=matched')
            ->assertSeeText([$matched->lcisProject->project_code, $flagged->lcisProject->project_code])
            ->assertDontSeeText($proposed->lcisProject->project_code);

        $this->actingAs($user)->get('/matches?review=1')
            ->assertSeeText($flagged->lcisProject->project_code)
            ->assertDontSeeText([$matched->lcisProject->project_code, $proposed->lcisProject->project_code]);
    }

    public function test_searches_both_records(): void
    {
        $byCode = ProjectMatch::factory()->create(['lcis_project_id' => LcisProject::factory()->create(['project_code' => 'AGR-0012'])]);
        $byNumber = ProjectMatch::factory()->create(['amis_contract_id' => AmisContract::factory()->create(['contract_number' => '5871/MTC'])]);
        $other = ProjectMatch::factory()->create();
        $user = User::factory()->lcis()->create();

        $this->actingAs($user)->get('/matches?search=AGR-0012')->assertSeeText('AGR-0012')->assertDontSeeText([$byNumber->amisContract->contract_number, $other->lcisProject->project_code]);
        $this->actingAs($user)->get('/matches?search=5871')->assertSeeText('5871/MTC')->assertDontSeeText($byCode->lcisProject->project_code);
    }

    public function test_an_invalid_filter_returns_to_the_unfiltered_list(): void
    {
        $this->actingAs(User::factory()->lcis()->create())->get('/matches?status=lost')->assertRedirect(route('matches.index'));
    }

    public function test_exports_the_filtered_matches_as_csv_readable_by_excel(): void
    {
        $matched = ProjectMatch::factory()->matched()->create([
            'lcis_project_id' => LcisProject::factory()->create(['project_code' => 'AGR-0012', 'activity_name' => 'ປູກກາເຟ']),
            'amis_contract_id' => AmisContract::factory()->create(['contract_number' => '5871/MTC', 'partner_names' => ['ບໍລິສັດ ກ', 'ບໍລິສັດ ຂ']]),
        ]);
        $proposed = ProjectMatch::factory()->create();

        $response = $this->actingAs(User::factory()->amis()->create())->get('/matches/export?status=matched');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=matches-', $response->headers->get('Content-Disposition'));

        $lines = explode("\n", trim($response->streamedContent()));
        $this->assertStringStartsWith("\u{FEFF}match_id,status,needs_review,lcis_project_id,lcis_project_code", $lines[0]);
        $this->assertCount(2, $lines);
        $row = str_getcsv($lines[1], escape: '');
        $this->assertSame([(string) $matched->id, 'matched', 'no'], array_slice($row, 0, 3));
        $this->assertSame(['AGR-0012', 'ປູກກາເຟ'], array_slice($row, 4, 2));
        $this->assertSame(['5871/MTC'], array_slice($row, 8, 1));
        $this->assertSame('ບໍລິສັດ ກ; ບໍລິສັດ ຂ', $row[10]);
        $this->assertStringNotContainsString($proposed->lcisProject->project_code, $response->streamedContent());
    }
}
