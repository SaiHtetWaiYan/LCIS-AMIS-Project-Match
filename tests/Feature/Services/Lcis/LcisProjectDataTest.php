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

    public function test_derives_the_province_of_a_district_level_project_from_its_district_code(): void
    {
        $raw = [...$this->fixture('lcis/project.json'), 'responsibleLevel' => 'district', 'responsibleProvinceId' => null, 'responsible_province_name' => null, 'responsibleDistrictId' => 611, 'responsible_district_name' => 'ມ. ພູຄູນ'];

        $project = LcisProjectData::fromApi($raw);

        $this->assertSame(6, $project->responsibleProvinceId);
        $this->assertSame(6, $project->locations[0]->provinceId);
        $this->assertSame(611, $project->locations[0]->districtId);
    }

    public function test_uses_the_granted_area_and_locations_lcis_sends(): void
    {
        $raw = $this->fixture('lcis/project.json');
        $raw['granted_area_ha'] = 250.75;
        $raw['locations'] = [
            ['dam_name' => 'Site A', 'id_prov' => 16, 'prov_name' => 'ຈຳປາສັກ', 'id_dist' => 1605, 'dist_name' => 'ມ. ປາກຊ່ອງ', 'id_vill' => 1605010, 'vill_name' => 'ບ. ລາດຕະນະ'],
            ['dam_name' => 'Site B', 'id_prov' => null, 'id_dist' => 1702, 'id_vill' => null],
        ];

        $project = LcisProjectData::fromApi($raw);

        $this->assertSame(250.75, $project->areaHa);
        $this->assertCount(2, $project->locations);
        $this->assertFalse($project->locations[0]->isFallback);
        $this->assertSame(1605, $project->locations[0]->districtId);
        $this->assertSame('ບ. ລາດຕະນະ', $project->locations[0]->villageName);
        $this->assertSame(17, $project->locations[1]->provinceId);
        $this->assertSame(1702, $project->locations[1]->districtId);
    }

    public function test_reads_a_zero_granted_area_as_unknown(): void
    {
        $project = LcisProjectData::fromApi([...$this->fixture('lcis/project.json'), 'granted_area_ha' => 0]);

        $this->assertNull($project->areaHa);
    }

    public function test_falls_back_when_no_location_names_a_province_or_district(): void
    {
        $raw = [...$this->fixture('lcis/project.json'), 'locations' => [['dam_name' => 'Unknown', 'id_prov' => null, 'id_dist' => null, 'id_vill' => null]]];

        $project = LcisProjectData::fromApi($raw);

        $this->assertCount(1, $project->locations);
        $this->assertTrue($project->locations[0]->isFallback);
        $this->assertSame(1601, $project->locations[0]->districtId);
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
