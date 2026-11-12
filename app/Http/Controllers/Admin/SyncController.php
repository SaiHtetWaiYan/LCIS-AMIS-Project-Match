<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SyncSource;
use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SyncAmisContracts;
use App\Jobs\SyncLcisProjects;
use App\Models\SyncRun;
use App\Services\Sync\DistrictCodeCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SyncController extends Controller
{
    public function index(DistrictCodeCheck $districtCodeCheck): View
    {
        Gate::authorize('viewAny', SyncRun::class);

        $sources = [];

        foreach (SyncSource::cases() as $source) {
            $sources[] = [
                'source' => $source,
                'latest' => SyncRun::query()->forSource($source)->with('triggeredBy:id,name')->latest('started_at')->first(),
                'lastSuccess' => SyncRun::query()->forSource($source)->where('status', SyncStatus::Succeeded)->latest('started_at')->first(),
            ];
        }

        return view('admin.sync.index', [
            'sources' => $sources,
            'runs' => SyncRun::query()->with('triggeredBy:id,name')->latest('started_at')->paginate(15),
            'districtCheck' => $districtCodeCheck->run(),
        ]);
    }

    public function store(Request $request, SyncSource $source): RedirectResponse
    {
        Gate::authorize('create', SyncRun::class);

        $isRunning = SyncRun::query()->forSource($source)->where('status', SyncStatus::Running)->exists();

        if ($isRunning) {
            return back()->with('error', __('sync.already_running', ['source' => $source->label()]));
        }

        match ($source) {
            SyncSource::Lcis => SyncLcisProjects::dispatch($request->user()->id),
            SyncSource::Amis => SyncAmisContracts::dispatch($request->user()->id),
        };

        return back()->with('status', __('sync.queued', ['source' => $source->label()]));
    }
}
