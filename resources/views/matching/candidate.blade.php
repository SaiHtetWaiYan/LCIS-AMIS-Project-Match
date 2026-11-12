{{-- One suggested or searched record from the other system with its score breakdown. --}}
@php($record = $candidate->record)

<li class="py-5 first:pt-0 border-b border-blue-gray-50 last:border-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            @if ($record instanceof App\Models\AmisContract)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs text-gray-500">{{ $record->contract_number ?? '#'.$record->id }}</span>
                    @if ($record->status && $record->status !== 'nomal')
                        <x-badge tone="amber">{{ __('amis.statuses.'.$record->status) }}</x-badge>
                    @endif
                </div>
                <a href="{{ route('amis-contracts.show', $record) }}" class="mt-1 block font-medium text-blue-gray-900 hover:underline">{{ $record->contract_name ?? __('amis.contract') }}</a>
                <p class="text-sm text-gray-500">{{ $record->companies->isNotEmpty() ? $record->companies->pluck('name_lao')->filter()->implode(', ') : implode(', ', $record->partner_names) }}</p>
                <p class="text-sm text-gray-500">
                    {{ $record->items->pluck('district_name')->filter()->unique()->implode(', ') }}
                    @if ($record->area_ha !== null) · <x-hectares :value="$record->area_ha" /> @endif
                </p>
            @else
                <span class="font-mono text-xs text-gray-500">{{ $record->project_code ?? '#'.$record->id }}</span>
                <a href="{{ route('lcis-projects.show', $record) }}" class="mt-1 block font-medium text-blue-gray-900 hover:underline">{{ $record->activity_name ?? __('lcis.project') }}</a>
                <p class="text-sm text-gray-500">{{ $record->company_name }}@if ($record->company?->name_eng) · {{ $record->company->name_eng }}@endif</p>
                <p class="text-sm text-gray-500">
                    {{ $record->locations->map(fn ($location) => $location->district_name ?? ($provinces[$location->province_id] ?? null))->filter()->unique()->implode(', ') }}
                    @if ($record->area_ha !== null) · <x-hectares :value="$record->area_ha" /> @endif
                </p>
            @endif
        </div>

        <x-match-score :card="$candidate->score" class="shrink-0" />
    </div>

    <x-rule-outcomes :card="$candidate->score" class="mt-3" />
</li>
