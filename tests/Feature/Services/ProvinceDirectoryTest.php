<?php

namespace Tests\Feature\Services;

use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Services\ProvinceDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvinceDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_collects_province_names_from_both_systems_ordered_by_code(): void
    {
        LcisProject::factory()->create(['responsible_province_id' => 16, 'responsible_province_name' => 'ຈຳປາສັກ']);
        LcisProject::factory()->create(['responsible_province_id' => 1, 'responsible_province_name' => 'ນະຄອນຫຼວງວຽງຈັນ']);
        AmisContract::factory()->create(['from_province_id' => 14, 'raw' => ['from_province_name' => 'ສາລະວັນ']]);

        $names = app(ProvinceDirectory::class)->all();

        $this->assertSame([1 => 'ນະຄອນຫຼວງວຽງຈັນ', 14 => 'ສາລະວັນ', 16 => 'ຈຳປາສັກ'], $names);
    }

    public function test_names_an_unknown_code_by_its_number(): void
    {
        $this->assertSame('Province 9', app(ProvinceDirectory::class)->name(9));
        $this->assertNull(app(ProvinceDirectory::class)->name(null));
    }
}
