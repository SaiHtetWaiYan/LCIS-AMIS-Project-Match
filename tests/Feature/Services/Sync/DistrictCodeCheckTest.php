<?php

namespace Tests\Feature\Services\Sync;

use App\Models\AmisContractItem;
use App\Models\LcisProject;
use App\Services\Sync\DistrictCodeCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistrictCodeCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_codes_whose_names_differ_between_lcis_and_amis(): void
    {
        LcisProject::factory()->create(['responsible_district_id' => 104, 'responsible_district_name' => 'ສີສັດຕະນາກ']);
        LcisProject::factory()->create(['responsible_district_id' => 103, 'responsible_district_name' => 'ໄຊເສດຖາ']);
        AmisContractItem::factory()->create(['district_id' => 104, 'district_name' => 'ໄຊເສດຖາ']);
        AmisContractItem::factory()->create(['district_id' => 103, 'district_name' => "ເມືອງ ໄຊ\u{200B}ເສດຖາ"]);
        AmisContractItem::factory()->create(['district_id' => 1601, 'district_name' => 'ປາກເຊ']);

        $result = app(DistrictCodeCheck::class)->run();

        $this->assertSame(2, $result['compared']);
        $this->assertSame([['code' => 104, 'lcis' => ['ສີສັດຕະນາກ'], 'amis' => ['ໄຊເສດຖາ']]], $result['mismatches']);
    }
}
