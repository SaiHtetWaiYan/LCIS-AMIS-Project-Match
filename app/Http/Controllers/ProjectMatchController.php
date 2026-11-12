<?php

namespace App\Http\Controllers;

use App\Enums\UserSide;
use App\Http\Requests\ProposeMatchRequest;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Services\Matches\MatchWorkflow;
use App\Services\Matching\CandidateFinder;
use App\Services\ProvinceDirectory;
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

    public function show(ProjectMatch $match, CandidateFinder $candidates, ProvinceDirectory $provinces): View
    {
        Gate::authorize('view', $match);

        $match->load(['lcisProject.locations', 'lcisProject.company', 'amisContract.items', 'amisContract.companies', 'proposer', 'lcisConfirmer', 'amisConfirmer', 'events.user']);

        return view('matches.show', [
            'match' => $match,
            'score' => $candidates->scoreAmisContracts($match->lcisProject, $match->amisContract->newCollection([$match->amisContract]))[0]->score,
            'provinces' => $provinces->all(),
        ]);
    }
}
