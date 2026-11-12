<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAmisContractsRequest;
use App\Models\AmisContract;
use App\Services\ProvinceDirectory;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AmisContractController extends Controller
{
    public function index(ListAmisContractsRequest $request, ProvinceDirectory $provinces): View
    {
        $filters = $request->filters();

        $contracts = AmisContract::query()
            ->select(['id', 'contract_type', 'contract_number', 'contract_name', 'partner_names', 'status', 'from_province_id', 'area_ha', 'missing_since', 'starts_on', 'ends_on'])
            ->with(['items:id,amis_contract_id,province_id,district_id,district_name', 'companies:id,name_lao,name_eng'])
            ->when($filters['search'] !== '', fn ($query) => $query->search($filters['search']))
            ->when($filters['province'], fn ($query, int $provinceId) => $query->inProvince($provinceId))
            ->when($filters['type'], fn ($query, string $type) => $query->where('contract_type', $type))
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['source'], fn ($query, string $source) => $query->presentInSource($source === 'present'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('amis-contracts.index', [
            'contracts' => $contracts,
            'filters' => $filters,
            'provinces' => $provinces->all(),
            'contractTypes' => ListAmisContractsRequest::CONTRACT_TYPES,
            'statuses' => ListAmisContractsRequest::STATUSES,
        ]);
    }

    public function show(AmisContract $amisContract, ProvinceDirectory $provinces): View
    {
        Gate::authorize('view', $amisContract);

        return view('amis-contracts.show', [
            'contract' => $amisContract->load(['items', 'companies']),
            'provinces' => $provinces->all(),
        ]);
    }
}
