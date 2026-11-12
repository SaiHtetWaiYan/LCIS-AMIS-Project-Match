{{-- The two records of a match, compact, for list rows. --}}
<div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-6">
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            <x-badge tone="blue">{{ __('sync.sources.lcis') }}</x-badge>
            <span class="font-mono text-xs text-gray-500">{{ $match->lcisProject?->project_code ?? '#'.$match->lcis_project_id }}</span>
        </div>
        <p class="mt-1 text-blue-gray-900 break-words">{{ $match->lcisProject?->activity_name ?? __('app.not_available') }}</p>
        <p class="text-gray-500 break-words">{{ $match->lcisProject?->company_name }}</p>
    </div>
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            <x-badge tone="green">{{ __('sync.sources.amis') }}</x-badge>
            <span class="font-mono text-xs text-gray-500">{{ $match->amisContract?->contract_number ?? '#'.$match->amis_contract_id }}</span>
        </div>
        <p class="mt-1 text-blue-gray-900 break-words">{{ $match->amisContract?->contract_name ?? __('app.not_available') }}</p>
        <p class="text-gray-500 break-words">{{ implode(', ', $match->amisContract?->partner_names ?? []) }}</p>
    </div>
</div>
