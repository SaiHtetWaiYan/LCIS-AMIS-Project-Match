<x-app-layout>
    <x-slot name="title">{{ $project->project_code ?? __('lcis.project') }}</x-slot>
    <x-slot name="header">
        <div>
            <a href="{{ route('lcis-projects.index') }}" class="text-sm text-gray-500 hover:underline">&larr; {{ __('lcis.projects') }}</a>
            <x-page-title class="mt-1">{{ $project->activity_name ?? __('lcis.project') }}</x-page-title>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <x-badge tone="blue">{{ __('lcis.project') }}</x-badge>
                <span class="font-mono text-gray-600">{{ $project->project_code }}</span>
                @if ($project->isMissingFromSource())
                    <x-badge tone="red">{{ __('app.missing_since', ['date' => $project->missing_since->toDateString()]) }}</x-badge>
                @endif
                <span class="text-gray-500">{{ __('app.synced_at', ['date' => $project->synced_at->toDateTimeString()]) }}</span>
            </div>
        </div>
    </x-slot>

    <x-card :title="__('lcis.project')">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
            <x-field :label="__('lcis.fields.company')">{{ $project->company_name }}</x-field>
            <x-field :label="__('lcis.fields.company_name_eng')">{{ $project->company?->name_eng }}</x-field>
            <x-field :label="__('lcis.fields.activity_name')">{{ $project->activity_name }}</x-field>
            <x-field :label="__('lcis.fields.area')">
                @if ($project->area_ha !== null)
                    <x-hectares :value="$project->area_ha" />
                @else
                    <span class="text-gray-400">{{ __('lcis.area_pending') }}</span>
                @endif
            </x-field>
            <x-field :label="__('lcis.fields.project_type')">{{ $project->project_type }}</x-field>
            <x-field :label="__('lcis.fields.subsector')">{{ $project->subsector_name }}</x-field>
            <x-field :label="__('lcis.fields.status')">{{ $project->status }}</x-field>
            <x-field :label="__('lcis.fields.negotiation_status')">{{ $project->negotiation_status_name }}</x-field>
            <x-field :label="__('lcis.fields.responsible')">{{ collect([$project->responsible_level, $project->responsible_province_name, $project->responsible_district_name])->filter()->implode(' · ') }}</x-field>
            <x-field :label="__('lcis.fields.id')"><span class="font-mono">{{ $project->id }}</span></x-field>
            <x-field :label="__('lcis.fields.updated_at')">{{ $project->source_updated_at?->toDateTimeString() }}</x-field>
        </dl>
    </x-card>

    <x-card :title="__('lcis.locations.title')" :subtitle="$project->locations->contains('is_fallback', true) ? __('lcis.locations.fallback_hint') : null">
        @if ($project->locations->isEmpty())
            <p class="text-sm text-gray-500">{{ __('lcis.locations.empty') }}</p>
        @else
            <x-data-table :headings="[__('lcis.locations.province'), __('lcis.locations.district'), __('lcis.locations.village')]">
                @foreach ($project->locations as $location)
                    <tr class="block md:table-row px-4 py-3 md:p-0 border-b border-blue-gray-50">
                        <td class="block md:table-cell md:px-6 md:py-3">{{ $provinces[$location->province_id] ?? $location->province_name ?? __('app.not_available') }}</td>
                        <td class="block md:table-cell md:px-6 md:py-3">{{ $location->district_name ?? __('app.not_available') }} <span class="font-mono text-xs text-gray-400">{{ $location->district_id }}</span></td>
                        <td class="block md:table-cell md:px-6 md:py-3">{{ $location->village_name ?? __('app.not_available') }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    @include('matching.panel', ['otherSide' => 'amis'])

    <x-raw-data :raw="$project->raw" />
</x-app-layout>
