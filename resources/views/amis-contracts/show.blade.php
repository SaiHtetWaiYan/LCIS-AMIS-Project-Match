<x-app-layout>
    <x-slot name="title">{{ $contract->contract_number ?? __('amis.contract') }}</x-slot>
    <x-slot name="header">
        <div>
            <a href="{{ route('amis-contracts.index') }}" class="text-sm text-gray-500 hover:underline">&larr; {{ __('amis.contracts') }}</a>
            <x-page-title class="mt-1">{{ $contract->contract_name ?? __('amis.contract') }}</x-page-title>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                <x-badge tone="green">{{ __('amis.contract') }}</x-badge>
                <span class="font-mono text-gray-600">{{ $contract->contract_number }}</span>
                @if ($contract->status && $contract->status !== 'nomal')
                    <x-badge tone="amber">{{ __('amis.statuses.'.$contract->status) }}</x-badge>
                @endif
                @if ($contract->isMissingFromSource())
                    <x-badge tone="red">{{ __('app.missing_since', ['date' => $contract->missing_since->toDateString()]) }}</x-badge>
                @endif
                <span class="text-gray-500">{{ __('app.synced_at', ['date' => $contract->synced_at->toDateTimeString()]) }}</span>
            </div>
        </div>
    </x-slot>

    <x-card :title="__('amis.contract')">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
            <x-field :label="__('amis.fields.partners')">{{ implode(', ', $contract->partner_names) }}</x-field>
            <x-field :label="__('amis.fields.contract_name')">{{ $contract->contract_name }}</x-field>
            <x-field :label="__('amis.fields.area')"><x-hectares :value="$contract->area_ha" /></x-field>
            <x-field :label="__('amis.fields.contract_type')">{{ $contract->contract_type ? __('amis.contract_types.'.$contract->contract_type) : '' }}</x-field>
            <x-field :label="__('amis.fields.business_type')">{{ $contract->business_type_name }}</x-field>
            <x-field :label="__('amis.fields.status')">{{ $contract->status ? __('amis.statuses.'.$contract->status) : '' }}</x-field>
            <x-field :label="__('amis.fields.period')">{{ collect([$contract->starts_on?->toDateString(), $contract->ends_on?->toDateString()])->filter()->implode(' → ') }}</x-field>
            <x-field :label="__('amis.fields.budget_province')">{{ $contract->from_province_id ? ($provinces[$contract->from_province_id] ?? __('app.province_code', ['code' => $contract->from_province_id])) : '' }}</x-field>
            <x-field :label="__('amis.fields.id')"><span class="font-mono">{{ $contract->id }}</span></x-field>
            <x-field :label="__('amis.fields.updated_at')">{{ $contract->source_updated_at?->toDateTimeString() }}</x-field>
        </dl>
    </x-card>

    <x-card :title="__('amis.fields.companies')">
        @if ($contract->companies->isEmpty())
            <p class="text-sm text-gray-500">{{ __('amis.companies.none') }}</p>
        @else
            <x-data-table :headings="[__('amis.companies.name'), __('amis.companies.tax_id'), __('amis.companies.share')]">
                @foreach ($contract->companies as $company)
                    <tr class="block md:table-row px-4 py-3 md:p-0 border-b border-blue-gray-50">
                        <td class="block md:table-cell md:px-6 md:py-3">
                            <div class="font-medium text-blue-gray-900">{{ $company->name_lao }}</div>
                            <div class="text-gray-500">{{ $company->name_eng }}</div>
                        </td>
                        <td class="block md:table-cell md:px-6 md:py-3 font-mono">{{ $company->tax_id ?? __('app.not_available') }}</td>
                        <td class="block md:table-cell md:px-6 md:py-3 tabular-nums">{{ $company->pivot->shareholding_pct !== null ? rtrim(rtrim($company->pivot->shareholding_pct, '0'), '.').'%' : __('app.not_available') }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    <x-card :title="__('amis.items.title')">
        @if ($contract->items->isEmpty())
            <p class="text-sm text-gray-500">{{ __('amis.items.empty') }}</p>
        @else
            <x-data-table :headings="[__('amis.items.land_use'), __('amis.items.province'), __('amis.items.district'), __('amis.items.village'), __('amis.items.area'), __('amis.items.area_ha')]">
                @foreach ($contract->items as $item)
                    <tr class="block md:table-row px-4 py-3 md:p-0 border-b border-blue-gray-50">
                        <td class="block md:table-cell md:px-6 md:py-3">{{ $item->land_use_detail ?? __('app.not_available') }}</td>
                        <td class="block md:table-cell md:px-6 md:py-3">{{ $item->province_id ? ($provinces[$item->province_id] ?? __('app.province_code', ['code' => $item->province_id])) : __('app.not_available') }}</td>
                        <td class="block md:table-cell md:px-6 md:py-3">{{ $item->district_name ?? __('app.not_available') }} <span class="font-mono text-xs text-gray-400">{{ $item->district_id }}</span></td>
                        <td class="block md:table-cell md:px-6 md:py-3">{{ $item->village ?? __('app.not_available') }}</td>
                        <td class="block md:table-cell md:px-6 md:py-3 tabular-nums">{{ $item->area !== null ? rtrim(rtrim($item->area, '0'), '.').' '.$item->unit : __('app.not_available') }}</td>
                        <td class="block md:table-cell md:px-6 md:py-3">
                            @if ($item->area !== null && $item->area > 0 && $item->area_ha === null)
                                <x-badge tone="amber">{{ __('amis.items.unknown_unit') }}</x-badge>
                            @else
                                <x-hectares :value="$item->area_ha" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </x-card>

    @include('matching.panel', ['otherSide' => 'lcis'])

    <x-raw-data :raw="$contract->raw" />
</x-app-layout>
