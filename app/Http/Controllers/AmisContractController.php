<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateSearchRequest;
use App\Http\Requests\ListAmisContractsRequest;
use App\Models\AmisContract;
use App\Services\Matching\CandidateFinder;
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
            ->with(['items:id,amis_contract_id,province_id,district_id,district_name', 'companies:id,name_lao,name_eng', 'activeMatch:id,amis_contract_id,status,needs_review'])
            ->when($filters['search'] !== '', fn ($query) => $query->search($filters['search']))
            ->when($filters['province'], fn ($query, int $provinceId) => $query->inProvince($provinceId))
            ->when($filters['type'], fn ($query, string $type) => $query->where('contract_type', $type))
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['source'], fn ($query, string $source) => $query->presentInSource($source === 'present'))
            ->when($filters['match'], fn ($query, string $match) => $query->withMatchStatus($match))
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

    public function show(CandidateSearchRequest $request, AmisContract $amisContract, ProvinceDirectory $provinces, CandidateFinder $candidates): View
    {
        Gate::authorize('view', $amisContract);

        $amisContract->load(['items', 'companies', 'activeMatch']);
        $searchTerm = $request->searchTerm();

        return view('amis-contracts.show', [
            'contract' => $amisContract,
            'provinces' => $provinces->all(),
            'candidates' => $candidates->forAmisContract($amisContract),
            'searchTerm' => $searchTerm,
            'searchResults' => $searchTerm === '' ? null : $candidates->scoreLcisProjects(
                $amisContract,
                $candidates->lcisQuery()->search($searchTerm)->orderByDesc('source_updated_at')->limit(20)->get(),
            ),
        ]);
    }
}
