<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Enums\UserSide;
use App\Models\AmisContract;
use App\Models\LcisProject;
use App\Models\ProjectMatch;
use App\Models\SyncRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $side = $request->user()->side;
        $statusCounts = ProjectMatch::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('dashboard', [
            'side' => $side,
            'unmatchedLcis' => LcisProject::query()->presentInSource()->withMatchStatus('unmatched')->count(),
            'totalLcis' => LcisProject::query()->presentInSource()->count(),
            'unmatchedAmis' => AmisContract::query()->presentInSource()->withMatchStatus('unmatched')->count(),
            'totalAmis' => AmisContract::query()->presentInSource()->count(),
            'awaitingMe' => $side === UserSide::Admin ? null : ProjectMatch::query()->awaitingSide($side)->count(),
            'statusCounts' => collect(MatchStatus::cases())->mapWithKeys(fn (MatchStatus $status): array => [$status->value => (int) ($statusCounts[$status->value] ?? 0)]),
            'needsReview' => ProjectMatch::query()->active()->where('needs_review', true)->count(),
            'syncs' => collect(SyncSource::cases())->map(fn (SyncSource $source): array => [
                'source' => $source,
                'latest' => SyncRun::query()->forSource($source)->latest('started_at')->first(),
                'lastSuccess' => SyncRun::query()->forSource($source)->where('status', SyncStatus::Succeeded)->latest('started_at')->first(),
            ]),
        ]);
    }
}
