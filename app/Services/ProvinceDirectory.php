<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Province names by code, taken from the synced data. LCIS and AMIS share the national province
 * codes (1 = ນະຄອນຫຼວງວຽງຈັນ), so one list serves both sides.
 */
class ProvinceDirectory
{
    /**
     * @var array<int, string>|null
     */
    private ?array $names = null;

    /**
     * @return array<int, string> name by province code, ordered by code
     */
    public function all(): array
    {
        if ($this->names !== null) {
            return $this->names;
        }

        $rows = DB::table('lcis_projects')
            ->selectRaw('responsible_province_id as id, responsible_province_name as name')
            ->whereNotNull('responsible_province_id')->whereNotNull('responsible_province_name')
            ->union(DB::table('lcis_project_locations')
                ->selectRaw('province_id as id, province_name as name')
                ->whereNotNull('province_id')->whereNotNull('province_name'))
            ->union(DB::table('amis_contracts')
                ->selectRaw("from_province_id as id, raw->>'from_province_name' as name")
                ->whereNotNull('from_province_id')->whereRaw("coalesce(raw->>'from_province_name', '') <> ''"))
            ->get();

        $names = [];

        foreach ($rows as $row) {
            $names[(int) $row->id] ??= trim($row->name);
        }

        ksort($names);

        return $this->names = $names;
    }

    public function name(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        return $this->all()[$id] ?? __('app.province_code', ['code' => $id]);
    }
}
