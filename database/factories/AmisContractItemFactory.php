<?php

namespace Database\Factories;

use App\Models\AmisContract;
use App\Models\AmisContractItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AmisContractItem>
 */
class AmisContractItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 9_999_999),
            'amis_contract_id' => AmisContract::factory(),
            'land_use_detail' => 'ກະສິກຳ',
            'province_id' => 1,
            'district_id' => 104,
            'district_name' => 'ສີສັດຕະນາກ',
            'village' => 'ພະໂພ',
            'area' => 500,
            'unit' => 'ເຮັກຕາ',
            'area_ha' => 500,
        ];
    }
}
