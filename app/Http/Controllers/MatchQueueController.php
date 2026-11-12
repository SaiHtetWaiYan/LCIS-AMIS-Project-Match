<?php

namespace App\Http\Controllers;

use App\Enums\UserSide;
use App\Models\ProjectMatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * What waits for the current user: proposals their side has to confirm or reject, oldest first.
 * Admins confirm nothing, so their queue is the active matches flagged for review.
 */
class MatchQueueController extends Controller
{
    public function __invoke(Request $request): View
    {
        $side = $request->user()->side;

        $matches = ProjectMatch::query()
            ->when(
                $side === UserSide::Admin,
                fn ($query) => $query->active()->where('needs_review', true),
                fn ($query) => $query->awaitingSide($side),
            )
            ->with(['lcisProject:id,project_code,activity_name,company_name', 'amisContract:id,contract_number,contract_name,partner_names', 'proposer:id,name,side'])
            ->oldest('updated_at')
            ->orderBy('id')
            ->paginate(25);

        return view('matches.queue', [
            'matches' => $matches,
            'isAdmin' => $side === UserSide::Admin,
        ]);
    }
}
