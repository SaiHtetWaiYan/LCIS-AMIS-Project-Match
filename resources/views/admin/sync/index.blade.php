<x-app-layout>
    <x-slot name="title">{{ __('sync.title') }}</x-slot>
    <x-slot name="header">
        <x-page-title>{{ __('sync.title') }}</x-page-title>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach ($sources as ['source' => $source, 'latest' => $latest, 'lastSuccess' => $lastSuccess])
            <x-card :title="$source->label()" :subtitle="__('sync.source_descriptions.'.$source->value)">
                @can('create', App\Models\SyncRun::class)
                    <x-slot name="actions">
                        <form method="POST" action="{{ route('admin.sync.store', $source) }}">
                            @csrf
                            <x-primary-button :disabled="$latest?->status === App\Enums\SyncStatus::Running" class="disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                {{ __('sync.run_now') }}
                            </x-primary-button>
                        </form>
                    </x-slot>
                @endcan

                @if ($latest === null)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('sync.never_synced') }}</p>
                @else
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                        @include('admin.sync.partials.status-badge', ['run' => $latest])
                        <span class="text-blue-gray-900 dark:text-gray-200">
                            {{ __('sync.last_sync') }}:
                            <time datetime="{{ $latest->started_at->toIso8601String() }}" title="{{ $latest->started_at->toDateTimeString() }}">{{ $latest->started_at->diffForHumans() }}</time>
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">· {{ $latest->triggeredBy?->name ?? __('sync.scheduler') }}</span>
                    </div>

                    @if ($lastSuccess && $lastSuccess->isNot($latest))
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('sync.last_success') }}: {{ $lastSuccess->started_at->toDateTimeString() }} ({{ $lastSuccess->started_at->diffForHumans() }})
                        </p>
                    @endif

                    @if ($latest->status === App\Enums\SyncStatus::Failed)
                        <div class="mt-4 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200" role="alert">
                            <p class="font-semibold">
                                {{ $latest->errorLabel() }}
                                <span class="font-mono text-xs font-normal">({{ $latest->error_code }})</span>
                            </p>
                            <p class="mt-1 break-words">{{ $latest->error_message }}</p>
                            @if ($latest->isAuthenticationFailure())
                                <p class="mt-2">{{ __('sync.auth_help.'.$source->value) }}</p>
                            @endif
                        </div>
                    @endif

                    <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ([
                            'fetched' => $latest->fetched_count.($latest->expected_count !== null ? ' / '.$latest->expected_count : ''),
                            'created' => $latest->created_count,
                            'updated' => $latest->updated_count,
                            'unchanged' => $latest->unchanged_count,
                            'missing' => $latest->missing_count,
                            'restored' => $latest->restored_count,
                        ] as $label => $value)
                            <div class="rounded-lg bg-blue-gray-50/60 dark:bg-gray-900/40 px-3 py-2">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('sync.fields.'.$label) }}</dt>
                                <dd class="text-lg font-semibold text-blue-gray-900 dark:text-gray-100 tabular-nums">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @php($details = $latest->details ?? [])
                    <ul class="mt-4 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        @if ($latest->expected_count !== null && $latest->status === App\Enums\SyncStatus::Succeeded && $latest->expected_count !== $latest->fetched_count)
                            <li class="text-amber-700 dark:text-amber-300">{{ __('sync.expected_mismatch', ['expected' => $latest->expected_count, 'fetched' => $latest->fetched_count]) }}</li>
                        @endif
                        @isset($details['companies'])
                            <li>{{ __('sync.company_counts', $details['companies']) }}</li>
                        @endisset
                        @isset($details['company_links'])
                            <li>{{ __('sync.company_links', ['count' => $details['company_links']]) }}</li>
                        @endisset
                        @if (($details['skipped'] ?? 0) > 0)
                            <li class="text-amber-700 dark:text-amber-300">{{ __('sync.skipped', ['count' => $details['skipped']]) }}</li>
                        @endif
                    </ul>

                    @if (! empty($details['unknown_units']))
                        <div class="mt-4 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/30 p-4 text-sm text-amber-900 dark:text-amber-100">
                            <p class="font-semibold">{{ __('sync.unknown_units.title') }}</p>
                            <p class="mt-1">{{ __('sync.unknown_units.hint') }}</p>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($details['unknown_units'] as $unit => $count)
                                    <li><x-badge tone="amber">“{{ $unit === '' ? '—' : $unit }}” · {{ __('sync.unknown_units.items', ['count' => $count]) }}</x-badge></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </x-card>
        @endforeach
    </div>

    <x-card :title="__('sync.districts.title')" :subtitle="__('sync.districts.hint')">
        @if ($districtCheck['compared'] === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('sync.districts.no_data') }}</p>
        @else
            <p class="text-sm">
                {{ __('sync.districts.compared', ['count' => $districtCheck['compared']]) }}
                @if ($districtCheck['mismatches'] === [])
                    <span class="text-green-700 dark:text-green-400 font-medium">{{ __('sync.districts.all_match') }}</span>
                @else
                    <span class="text-red-700 dark:text-red-400 font-medium">{{ __('sync.districts.mismatches', ['count' => count($districtCheck['mismatches'])]) }}</span>
                @endif
            </p>

            @if ($districtCheck['mismatches'] !== [])
                <div class="-mx-4 sm:-mx-6 mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr>
                                @foreach ([__('sync.districts.code'), __('sync.districts.lcis_names'), __('sync.districts.amis_names')] as $heading)
                                    <th scope="col" class="px-6 py-3 border-y border-blue-gray-100 dark:border-gray-700 bg-blue-gray-50/50 dark:bg-gray-900/40 font-normal text-blue-gray-900/70 dark:text-gray-400">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($districtCheck['mismatches'] as $mismatch)
                                <tr class="border-b border-blue-gray-50 dark:border-gray-700">
                                    <td class="px-6 py-3 font-mono">{{ $mismatch['code'] }}</td>
                                    <td class="px-6 py-3">{{ implode(', ', $mismatch['lcis']) }}</td>
                                    <td class="px-6 py-3">{{ implode(', ', $mismatch['amis']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </x-card>

    <x-card :title="__('sync.recent_runs')">
        @if ($runs->isEmpty())
            <p class="p-4 text-center text-sm text-blue-gray-900 dark:text-gray-300">{{ __('sync.no_runs') }}</p>
        @else
            <div class="-mx-4 sm:-mx-6 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr>
                            @foreach (['source', 'status', 'started_at', 'duration', 'triggered_by', 'fetched', 'created', 'updated', 'missing', 'error'] as $column)
                                <th scope="col" class="px-4 py-3 border-y border-blue-gray-100 dark:border-gray-700 bg-blue-gray-50/50 dark:bg-gray-900/40 font-normal text-blue-gray-900/70 dark:text-gray-400 whitespace-nowrap">{{ __('sync.fields.'.$column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runs as $run)
                            <tr class="border-b border-blue-gray-50 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-medium">{{ $run->source->label() }}</td>
                                <td class="px-4 py-3">@include('admin.sync.partials.status-badge', ['run' => $run])</td>
                                <td class="px-4 py-3 whitespace-nowrap tabular-nums">{{ $run->started_at->toDateTimeString() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap tabular-nums">{{ $run->finished_at ? $run->started_at->diffForHumans($run->finished_at, ['syntax' => Carbon\CarbonInterface::DIFF_ABSOLUTE, 'short' => true]) : '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $run->triggeredBy?->name ?? __('sync.scheduler') }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $run->fetched_count }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $run->created_count }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $run->updated_count }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ $run->missing_count }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-red-700 dark:text-red-400" title="{{ $run->error_message }}">{{ $run->error_code }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-pagination :paginator="$runs" />
    </x-card>
</x-app-layout>
