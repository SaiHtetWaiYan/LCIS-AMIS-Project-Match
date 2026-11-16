<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateSearchRequest;
use App\Http\Requests\ListLcisProjectsRequest;
use App\Models\LcisProject;
use App\Services\Matching\CandidateFinder;
use App\Services\ProvinceDirectory;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LcisProjectController extends Controller
{
    public function index(ListLcisProjectsRequest $request, ProvinceDirectory $provinces): View
    {
        $filters = $request->filters();

        $projects = LcisProject::query()
            ->select(['id', 'project_code', 'activity_name', 'project_type', 'status', 'company_id', 'company_name', 'area_ha', 'missing_since', 'source_updated_at', 'best_score', 'best_counterpart_id', 'best_is_pair', 'best_scored_at'])
            ->with(['locations', 'company:id,name_eng', 'activeMatch:id,lcis_project_id,status,needs_review', 'bestCounterpart:id,contract_number'])
            ->when($filters['search'] !== '', fn ($query) => $query->search($filters['search']))
            ->when($filters['province'], fn ($query, int $provinceId) => $query->inProvince($provinceId))
            ->when($filters['type'], fn ($query, int $typeId) => $query->where('project_type_id', $typeId))
            ->when($filters['source'], fn ($query, string $source) => $query->presentInSource($source === 'present'))
            ->when($filters['match'], fn ($query, string $match) => $query->withMatchStatus($match))
            ->when($filters['min_score'], fn ($query, int $minScore) => $query->where('best_score', '>=', $minScore / 100))
            ->when($filters['sort'] === 'score', fn ($query) => $query->orderByRaw('best_score desc nulls last'))
            ->orderByDesc('source_updated_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('lcis-projects.index', [
            'projects' => $projects,
            'filters' => $filters,
            'minScores' => ListLcisProjectsRequest::MIN_SCORES,
            'provinces' => $provinces->all(),
            'projectTypes' => LcisProject::query()->whereNotNull('project_type_id')->distinct()->orderBy('project_type')->pluck('project_type', 'project_type_id'),
        ]);
    }

    public function show(CandidateSearchRequest $request, LcisProject $lcisProject, ProvinceDirectory $provinces, CandidateFinder $candidates): View
    {
        Gate::authorize('view', $lcisProject);

        $lcisProject->load(['locations', 'company', 'activeMatch']);
        $searchTerm = $request->searchTerm();

        return view('lcis-projects.show', [
            'project' => $lcisProject,
            'provinces' => $provinces->all(),
            'candidates' => $candidates->forLcisProject($lcisProject),
            'searchTerm' => $searchTerm,
            'searchResults' => $searchTerm === '' ? null : $candidates->scoreAmisContracts(
                $lcisProject,
                $candidates->amisQuery()->search($searchTerm)->orderByDesc('id')->limit(20)->get(),
            ),
        ]);
    }
}
