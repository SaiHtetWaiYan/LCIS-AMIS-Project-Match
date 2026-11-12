<x-app-layout>
    <x-slot name="title">{{ __('lcis.projects') }}</x-slot>

    <x-card :title="__('app.filters.title')">
        <form method="GET" action="{{ route('lcis-projects.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <x-input-label for="search" :value="__('app.filters.search')" />
                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="$filters['search']" :placeholder="__('lcis.search_hint')" />
            </div>
            <div>
                <x-input-label for="province" :value="__('app.filters.province')" />
                <x-select-input id="province" name="province" class="mt-1 block w-full">
                    <option value="">{{ __('app.filters.all_provinces') }}</option>
                    @foreach ($provinces as $id => $name)
                        <option value="{{ $id }}" @selected($filters['province'] === $id)>{{ $name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="type" :value="__('lcis.fields.project_type')" />
                <x-select-input id="type" name="type" class="mt-1 block w-full">
                    <option value="">{{ __('lcis.all_types') }}</option>
                    @foreach ($projectTypes as $id => $name)
                        <option value="{{ $id }}" @selected($filters['type'] === $id)>{{ $name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="source" :value="__('app.filters.source')" />
                <x-select-input id="source" name="source" class="mt-1 block w-full">
                    <option value="">{{ __('app.filters.all_records') }}</option>
                    @foreach (['present', 'missing'] as $source)
                        <option value="{{ $source }}" @selected($filters['source'] === $source)>{{ __('app.filters.'.$source) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="match" :value="__('app.filters.match_status')" />
                <x-select-input id="match" name="match" class="mt-1 block w-full">
                    <option value="">{{ __('app.filters.any_match_status') }}</option>
                    @foreach (['unmatched', 'proposed', 'matched'] as $matchStatus)
                        <option value="{{ $matchStatus }}" @selected($filters['match'] === $matchStatus)>{{ __('app.filters.'.$matchStatus) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div class="flex items-end justify-end gap-3 sm:col-span-2 lg:col-span-5">
                <a href="{{ route('lcis-projects.index') }}"><x-secondary-button>{{ __('app.filters.reset') }}</x-secondary-button></a>
                <x-primary-button>{{ __('app.filters.apply') }}</x-primary-button>
            </div>
        </form>
    </x-card>

    <x-card :title="__('lcis.projects')">
        @if ($projects->isEmpty())
            <p class="p-4 text-center text-sm text-blue-gray-900">{{ __('lcis.empty') }}</p>
        @else
            <x-data-table :headings="['#', __('lcis.fields.activity_name'), __('lcis.fields.project_type'), __('lcis.fields.location'), __('lcis.fields.area'), '']">
                @foreach ($projects as $project)
                    @php($location = $project->locations->first())
                    <tr class="block md:table-row px-4 py-4 md:p-0 border-b border-blue-gray-50 hover:bg-slate-50">
                        <td class="hidden md:table-cell px-6 py-4 align-top text-blue-gray-900">{{ $projects->firstItem() + $loop->index }}</td>
                        <td class="block md:table-cell md:px-6 md:py-4 align-top">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs text-gray-500">{{ $project->project_code ?? '#'.$project->id }}</span>
                                <x-match-badge :match="$project->activeMatch" />
                                @if ($project->isMissingFromSource())
                                    <x-badge tone="red">{{ __('app.filters.missing') }}</x-badge>
                                @endif
                            </div>
                            <a href="{{ route('lcis-projects.show', $project) }}" class="mt-1 block font-medium text-blue-gray-900 hover:underline">{{ $project->activity_name ?? __('app.not_available') }}</a>
                            <div class="mt-0.5 text-gray-500">{{ $project->company_name }}@if ($project->company?->name_eng) · {{ $project->company->name_eng }}@endif</div>
                        </td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top">
                            @if ($project->project_type)
                                <x-badge>{{ $project->project_type }}</x-badge>
                            @endif
                        </td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top">
                            @if ($location)
                                <div>{{ $provinces[$location->province_id] ?? $location->province_name }}</div>
                                <div class="text-gray-500">{{ $location->district_name }}@if ($project->locations->count() > 1) · +{{ $project->locations->count() - 1 }}@endif</div>
                            @else
                                <span class="text-gray-400">{{ __('app.not_available') }}</span>
                            @endif
                        </td>
                        <td class="block md:table-cell mt-2 md:mt-0 md:px-6 md:py-4 align-top whitespace-nowrap"><x-hectares :value="$project->area_ha" /></td>
                        <td class="block md:table-cell mt-3 md:mt-0 md:px-6 md:py-4 align-top md:text-right">
                            <x-link-button variant="view" :href="route('lcis-projects.show', $project)">{{ __('app.view') }}</x-link-button>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif

        <x-pagination :paginator="$projects" />
    </x-card>
</x-app-layout>
