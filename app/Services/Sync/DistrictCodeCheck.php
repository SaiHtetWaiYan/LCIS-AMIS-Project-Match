<?php

namespace App\Services\Sync;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Checks the assumption that LCIS and AMIS use the same national district codes, by comparing
 * the district names each system stores for a code. Codes found in only one system are ignored.
 */
class DistrictCodeCheck
{
    /**
     * @return array{compared: int, mismatches: list<array{code: int, lcis: list<string>, amis: list<string>}>}
     */
    public function run(): array
    {
        $lcisNames = $this->namesByCode(
            DB::table('lcis_projects')->select(['responsible_district_id as code', 'responsible_district_name as name'])->distinct()
                ->union(DB::table('lcis_project_locations')->select(['district_id as code', 'district_name as name'])->distinct()),
        );
        $amisNames = $this->namesByCode(
            DB::table('amis_contract_items')->select(['district_id as code', 'district_name as name'])->distinct()
                ->union(DB::table('amis_companies')->select(['district_id as code', 'district_name as name'])->distinct()),
        );

        $sharedCodes = array_intersect(array_keys($lcisNames), array_keys($amisNames));
        sort($sharedCodes);
        $mismatches = [];

        foreach ($sharedCodes as $code) {
            $lcisNormalized = array_map(self::normalize(...), $lcisNames[$code]);
            $amisNormalized = array_map(self::normalize(...), $amisNames[$code]);

            if (array_intersect($lcisNormalized, $amisNormalized) === []) {
                $mismatches[] = ['code' => $code, 'lcis' => $lcisNames[$code], 'amis' => $amisNames[$code]];
            }
        }

        return ['compared' => count($sharedCodes), 'mismatches' => $mismatches];
    }

    /**
     * Ignores spacing, zero-width characters and the ເມືອງ (district) prefix.
     */
    public static function normalize(string $name): string
    {
        $name = preg_replace('/[\s\x{200B}-\x{200D}\x{FEFF}]+/u', '', mb_strtolower($name)) ?? $name;

        return preg_replace('/^(ເມືອງ|district)/u', '', $name) ?? $name;
    }

    /**
     * @return array<int, list<string>>
     */
    private function namesByCode(Builder $query): array
    {
        $names = [];

        foreach ($query->get() as $row) {
            if ($row->code !== null && $row->name !== null && trim($row->name) !== '') {
                $names[(int) $row->code][] = trim($row->name);
            }
        }

        return array_map(fn (array $list): array => array_values(array_unique($list)), $names);
    }
}
