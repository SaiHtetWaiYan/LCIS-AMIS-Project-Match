<?php

namespace App\Http\Controllers;

use App\Enums\UserSide;
use App\Http\Requests\ListMatchesRequest;
use App\Http\Requests\ProposeMatchRequest;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Services\Matches\MatchWorkflow;
use App\Services\Matching\PairComparer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectMatchController extends Controller
{
    public function store(ProposeMatchRequest $request, MatchWorkflow $workflow): RedirectResponse
    {
        $project = LcisProject::findOrFail($request->integer('lcis_project_id'));
        $contract = AmisContract::findOrFail($request->integer('amis_contract_id'));
        Gate::authorize('create', [ProjectMatch::class, $project, $contract]);

        $match = $workflow->propose($request->user(), $project, $contract);
        $otherSide = $request->user()->side === UserSide::Lcis ? UserSide::Amis : UserSide::Lcis;

        return redirect()->route('matches.show', $match)
            ->with('status', __('matches.flash.proposed', ['side' => $otherSide->label()]));
    }

    public function index(ListMatchesRequest $request): View
    {
        $filters = $request->filters();

        return view('matches.index', [
            'filters' => $filters,
            'matches' => ProjectMatch::query()
                ->filteredBy($filters)
                ->with(['lcisProject:id,project_code,activity_name,company_name', 'amisContract:id,contract_number,contract_name,partner_names', 'proposer:id,name'])
                ->latest('updated_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function show(ProjectMatch $match, PairComparer $comparer): View
    {
        Gate::authorize('view', $match);

        $match->load(['proposer', 'lcisConfirmer', 'amisConfirmer', 'events.user']);

        return view('compare.show', [
            'comparison' => $comparer->compare($match->lcisProject, $match->amisContract),
            'match' => $match,
            'earlierMatches' => ProjectMatch::query()
                ->where('lcis_project_id', $match->lcis_project_id)
                ->where('amis_contract_id', $match->amis_contract_id)
                ->whereKeyNot($match->id)
                ->latest('id')
                ->get(),
        ]);
    }
}
