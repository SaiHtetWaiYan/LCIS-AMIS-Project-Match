<?php

namespace Tests\Feature\Services\Amis;

use App\Exceptions\SourceApi\InvalidSourceRecordException;
use App\Services\Amis\AreaUnitConverter;
use App\Services\Amis\Data\AmisContractData;
use Tests\TestCase;

class AmisContractDataTest extends TestCase
{
    public function test_maps_the_misspelt_amis_fields(): void
    {
        $contract = AmisContractData::fromApi($this->fixture('amis/rent_land_legacy_partner.json'), new AreaUnitConverter);

        $this->assertSame(252, $contract->id);
        $this->assertSame('222/ບພພ,ມສນ', $contract->contractNumber);
        $this->assertSame('ສຳປະທານລານຈອດລົດ', $contract->contractName);
        $this->assertSame('2016-03-01', $contract->startsOn);
        $this->assertSame('2026-03-01', $contract->endsOn);
        $this->assertSame('nomal', $contract->status);
        $this->assertSame('ສຳປະທານລານຈອດລົດ', $contract->items[0]->landUseDetail);
        $this->assertSame(104, $contract->items[0]->districtId);
        $this->assertSame('2025-12-01T15:24:27+00:00', $contract->sourceUpdatedAt->toIso8601String());
    }

    public function test_derives_the_item_province_from_the_district_code_when_amis_sends_zero(): void
    {
        $contract = AmisContractData::fromApi($this->fixture('amis/rent_land_legacy_partner.json'), new AreaUnitConverter);

        $this->assertSame(1, $contract->items[0]->provinceId);
    }

    public function test_falls_back_to_the_partner_address_province_without_a_district(): void
    {
        $raw = $this->fixture('amis/rent_land_legacy_partner.json');
        $raw['items'][0]['dristric_id'] = '';
        $raw['partner_address']['province_id'] = 5;

        $contract = AmisContractData::fromApi($raw, new AreaUnitConverter);

        $this->assertNull($contract->items[0]->districtId);
        $this->assertSame(5, $contract->items[0]->provinceId);
    }

    public function test_keeps_partner_names_and_drops_json_company_links(): void
    {
        $legacy = AmisContractData::fromApi($this->fixture('amis/rent_land_legacy_partner.json'), new AreaUnitConverter);
        $linked = AmisContractData::fromApi($this->fixture('amis/rent_land_company_linked.json'), new AreaUnitConverter);

        $this->assertSame(['ບໍລິສັດບີເອັມຊີ'], $legacy->partnerNames);
        $this->assertSame([], $linked->partnerNames);
    }

    public function test_sums_item_areas_in_hectares_across_units(): void
    {
        $contract = AmisContractData::fromApi($this->fixture('amis/rent_land_company_linked.json'), new AreaUnitConverter);

        $this->assertEqualsWithDelta(121.0, $contract->areaHa(), 0.0001);
        $this->assertSame([], $contract->unknownUnits());
    }

    public function test_has_no_area_when_any_item_unit_is_unknown(): void
    {
        $raw = $this->fixture('amis/rent_land_company_linked.json');
        $raw['items'][1]['unit'] = 'ໄລ່';

        $contract = AmisContractData::fromApi($raw, new AreaUnitConverter);

        $this->assertNull($contract->areaHa());
        $this->assertSame(['ໄລ່'], $contract->unknownUnits());
    }

    public function test_treats_placeholder_dates_as_missing(): void
    {
        $raw = $this->fixture('amis/rent_land_legacy_partner.json');
        $raw['contact_date_start'] = '0000-00-00';
        $raw['contact_date_end'] = '2026-02-30';

        $contract = AmisContractData::fromApi($raw, new AreaUnitConverter);

        $this->assertNull($contract->startsOn);
        $this->assertNull($contract->endsOn);
    }

    public function test_rejects_a_contract_without_contact_id(): void
    {
        $raw = $this->fixture('amis/rent_land_legacy_partner.json');
        unset($raw['contact_id']);

        $this->expectException(InvalidSourceRecordException::class);

        AmisContractData::fromApi($raw, new AreaUnitConverter);
    }
}
