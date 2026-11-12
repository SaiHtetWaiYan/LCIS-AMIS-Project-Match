<x-app-layout>
    <x-slot name="title">{{ __('app.nav.dashboard') }}</x-slot>
    <x-slot name="header">
        <div>
            <x-page-title>{{ __('app.nav.dashboard') }}</x-page-title>
            <p class="mt-1 text-sm text-gray-500">{{ __('app.dashboard.welcome', ['name' => auth()->user()->name, 'side' => $side->label()]) }}</p>
        </div>
    </x-slot>

    <div @class(['grid grid-cols-1 gap-6 sm:grid-cols-2', 'lg:grid-cols-4' => $awaitingMe !== null, 'lg:grid-cols-3' => $awaitingMe === null])>
        @if ($awaitingMe !== null)
            <x-stat-tile :label="__('app.dashboard.awaiting_me')" :value="$awaitingMe" :hint="__('app.dashboard.awaiting_me_hint')" :href="route('matches.queue')" :highlight="$awaitingMe > 0" />
        @endif
        <x-stat-tile :label="__('app.dashboard.unmatched_lcis')" :value="$unmatchedLcis" :hint="__('app.dashboard.of_total', ['total' => number_format($totalLcis)])" :href="route('lcis-projects.index', ['match' => 'unmatched', 'source' => 'present'])" />
        <x-stat-tile :label="__('app.dashboard.unmatched_amis')" :value="$unmatchedAmis" :hint="__('app.dashboard.of_total', ['total' => number_format($totalAmis)])" :href="route('amis-contracts.index', ['match' => 'unmatched', 'source' => 'present'])" />
        <x-stat-tile :label="__('app.dashboard.needs_review')" :value="$needsReview" :hint="__('app.dashboard.needs_review_hint')" :href="$awaitingMe === null ? route('matches.queue') : route('matches.index', ['review' => 1])" :highlight="$needsReview > 0" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card :title="__('app.dashboard.matches')">
            <x-slot name="actions">
                <x-link-button variant="view" :href="route('matches.index')">{{ __('app.dashboard.all_matches') }}</x-link-button>
            </x-slot>
            <ul class="divide-y divide-blue-gray-50 text-sm">
                @foreach (App\Enums\MatchStatus::cases() as $status)
                    <li>
                        <a href="{{ route('matches.index', ['status' => $status->value]) }}" class="flex items-center justify-between gap-4 py-3 hover:bg-slate-50 -mx-2 px-2 rounded">
                            <x-badge :tone="$status->tone()">{{ $status->label() }}</x-badge>
                            <span class="text-lg font-semibold tabular-nums text-blue-gray-900">{{ number_format($statusCounts[$status->value]) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <x-card :title="__('app.dashboard.sync')">
            @can('viewAny', App\Models\SyncRun::class)
                <x-slot name="actions">
                    <x-link-button variant="view" :href="route('admin.sync.index')">{{ __('app.nav.sync') }}</x-link-button>
                </x-slot>
            @endcan
            <ul class="divide-y divide-blue-gray-50 text-sm">
                @foreach ($syncs as ['source' => $source, 'latest' => $latest, 'lastSuccess' => $lastSuccess])
                    <li class="py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-blue-gray-900">{{ $source->label() }}</span>
                            @if ($latest)
                                @include('admin.sync.partials.status-badge', ['run' => $latest])
                                <span class="text-gray-600">
                                    {{ __('app.dashboard.last_sync') }} <time datetime="{{ $latest->started_at->toIso8601String() }}" title="{{ $latest->started_at->toDateTimeString() }}">{{ $latest->started_at->diffForHumans() }}</time>
                                </span>
                            @else
                                <span class="text-gray-500">{{ __('app.dashboard.never_synced') }}</span>
                            @endif
                        </div>
                        @if ($latest?->status === App\Enums\SyncStatus::Failed)
                            <p class="mt-1 text-red-700">{{ $latest->errorLabel() }} <span class="font-mono text-xs">({{ $latest->error_code }})</span></p>
                            @if ($lastSuccess)
                                <p class="mt-1 text-xs text-gray-500">{{ __('app.dashboard.last_success', ['ago' => $lastSuccess->started_at->diffForHumans()]) }}</p>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</x-app-layout>
