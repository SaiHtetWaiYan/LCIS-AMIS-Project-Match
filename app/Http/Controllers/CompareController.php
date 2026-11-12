<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompareRequest;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Services\Matching\PairComparer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * An LCIS project and an AMIS contract side by side, before anyone has proposed them as a pair.
 * A pair that is already proposed or matched opens on its match page instead.
 */
class CompareController extends Controller
{
    public function show(CompareRequest $request, PairComparer $comparer): View|RedirectResponse
    {
        $project = LcisProject::findOrFail($request->integer('lcis'));
        $contract = AmisContract::findOrFail($request->integer('amis'));
        Gate::authorize('view', $project);
        Gate::authorize('view', $contract);

        $activeMatch = $project->activeMatch()->where('amis_contract_id', $contract->id)->first();

        if ($activeMatch !== null) {
            return redirect()->route('matches.show', $activeMatch);
        }

        $project->load('activeMatch');
        $contract->load('activeMatch');

        return view('compare.show', [
            'comparison' => $comparer->compare($project, $contract),
            'match' => null,
            'earlierMatches' => $project->matches()->where('amis_contract_id', $contract->id)->latest('id')->get(),
        ]);
    }
}
