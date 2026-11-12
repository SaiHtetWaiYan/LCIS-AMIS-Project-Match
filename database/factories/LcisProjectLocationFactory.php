<?php

namespace Database\Factories;

use App\Models\LcisProject;
use App\Models\LcisProjectLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LcisProjectLocation>
 */
class LcisProjectLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lcis_project_id' => LcisProject::factory(),
            'province_id' => 1,
            'province_name' => 'ນະຄອນຫຼວງວຽງຈັນ',
            'district_id' => 104,
            'district_name' => 'ສີສັດຕະນາກ',
            'village_id' => null,
            'village_name' => 'ພະໂພ',
            'is_fallback' => false,
        ];
    }
}
