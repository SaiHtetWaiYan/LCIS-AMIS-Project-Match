<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectMatchRequest;
use App\Http\Requests\ReviewMatchRequest;
use App\Http\Requests\UnmatchRequest;
use App\Models\ProjectMatch;
use App\Services\Matches\MatchWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The state changes of an existing match. Each re-checks its rule inside MatchWorkflow, so a
 * decision taken in another tab meanwhile ends in a friendly error instead of a wrong state.
 */
class MatchTransitionController extends Controller
{
    public function __construct(private readonly MatchWorkflow $workflow) {}

    public function confirm(Request $request, ProjectMatch $match): RedirectResponse
    {
        Gate::authorize('confirm', $match);
        $this->workflow->confirm($request->user(), $match);

        return $this->done($match, 'confirmed');
    }

    public function reject(RejectMatchRequest $request, ProjectMatch $match): RedirectResponse
    {
        Gate::authorize('reject', $match);
        $this->workflow->reject($request->user(), $match, $request->validated('reason'));

        return $this->done($match, 'rejected');
    }

    public function withdraw(Request $request, ProjectMatch $match): RedirectResponse
    {
        Gate::authorize('withdraw', $match);
        $this->workflow->withdraw($request->user(), $match);

        return $this->done($match, 'withdrawn');
    }

    public function unmatch(UnmatchRequest $request, ProjectMatch $match): RedirectResponse
    {
        Gate::authorize('unmatch', $match);
        $this->workflow->unmatch($request->user(), $match, $request->validated('reason'));

        return $this->done($match, 'unmatched');
    }

    public function review(ReviewMatchRequest $request, ProjectMatch $match): RedirectResponse
    {
        Gate::authorize('review', $match);
        $this->workflow->markReviewed($request->user(), $match, $request->validated('note'));

        return $this->done($match, 'reviewed');
    }

    private function done(ProjectMatch $match, string $outcome): RedirectResponse
    {
        return redirect()->route('matches.show', $match)->with('status', __('matches.flash.'.$outcome));
    }
}
