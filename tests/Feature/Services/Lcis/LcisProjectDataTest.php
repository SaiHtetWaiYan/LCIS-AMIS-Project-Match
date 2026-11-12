<?php

namespace Tests\Feature\Services\Lcis;

use App\Services\Lcis\Data\LcisProjectData;
use Tests\TestCase;

class LcisProjectDataTest extends TestCase
{
    public function test_falls_back_to_the_responsible_province_and_district_without_locations(): void
    {
        $project = LcisProjectData::fromApi($this->fixture('lcis/project.json'));

        $this->assertCount(1, $project->locations);
        $this->assertTrue($project->locations[0]->isFallback);
        $this->assertSame(16, $project->locations[0]->provinceId);
        $this->assertSame(1601, $project->locations[0]->districtId);
        $this->assertNull($project->areaHa);
    }

    public function test_uses_locations_and_area_once_lcis_sends_them(): void
    {
        $raw = $this->fixture('lcis/project.json');
        $raw['area_ha'] = '250.75';
        $raw['locations'] = [
            ['province_id' => 16, 'province_name' => 'ຈຳປາສັກ', 'district_id' => '1605', 'district_name' => 'ປາກຊ່ອງ', 'village_id' => 160501, 'village_name' => 'ບ້ານລາດຕະນະ'],
            ['province_id' => 17, 'district_id' => 1702],
        ];

        $project = LcisProjectData::fromApi($raw);

        $this->assertSame(250.75, $project->areaHa);
        $this->assertCount(2, $project->locations);
        $this->assertFalse($project->locations[0]->isFallback);
        $this->assertSame(1605, $project->locations[0]->districtId);
        $this->assertSame('ບ້ານລາດຕະນະ', $project->locations[0]->villageName);
        $this->assertSame(17, $project->locations[1]->provinceId);
    }

    public function test_key_fields_hash_ignores_fields_that_matching_does_not_use(): void
    {
        $raw = $this->fixture('lcis/project.json');
        $changedStatus = [...$raw, 'status' => 'suspended', 'updated_at' => '2026-11-01 00:00:00'];
        $changedCompany = [...$raw, 'company_name' => 'Another company'];

        $original = LcisProjectData::fromApi($raw)->keyFieldsHash();

        $this->assertSame($original, LcisProjectData::fromApi($changedStatus)->keyFieldsHash());
        $this->assertNotSame($original, LcisProjectData::fromApi($changedCompany)->keyFieldsHash());
    }
}
