@php
    $project = $match->lcisProject;
    $contract = $match->amisContract;
    $confirmation = fn ($user, $at) => $at ? __('matches.fields.by_at', ['name' => $user?->name ?? '—', 'date' => $at->toDateTimeString()]) : null;
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('matches.title', ['id' => $match->id]) }}</x-slot>
    <x-slot name="header">
        <div>
            <x-page-title>{{ __('matches.title', ['id' => $match->id]) }}</x-page-title>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <x-badge :tone="$match->status->tone()">{{ $match->status->label() }}</x-badge>
                @if ($match->needs_review)
                    <x-badge tone="amber">{{ __('matches.banner.needs_review') }}</x-badge>
                @endif
            </div>
        </div>
    </x-slot>

    @if ($match->needs_review)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
            {{ __('matches.review_hint', ['reasons' => collect($match->review_reasons)->map(fn ($reason) => __('matches.review_reasons.'.$reason))->implode('; ')]) }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card :title="__('lcis.project')">
            <x-slot name="actions">
                <x-link-button variant="view" :href="route('lcis-projects.show', $project)">{{ __('app.view') }}</x-link-button>
            </x-slot>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-field :label="__('lcis.fields.project_code')"><span class="font-mono">{{ $project->project_code }}</span></x-field>
                <x-field :label="__('lcis.fields.activity_name')">{{ $project->activity_name }}</x-field>
                <x-field :label="__('lcis.fields.company')">{{ $project->company_name }}</x-field>
                <x-field :label="__('lcis.fields.location')">{{ $project->locations->map(fn ($l) => collect([$provinces[$l->province_id] ?? $l->province_name, $l->district_name])->filter()->implode(' · '))->implode('; ') }}</x-field>
                <x-field :label="__('lcis.fields.area')">@if ($project->area_ha !== null)<x-hectares :value="$project->area_ha" />@endif</x-field>
                @if ($project->isMissingFromSource())
                    <x-field :label="__('app.filters.source')"><x-badge tone="red">{{ __('app.missing_since', ['date' => $project->missing_since->toDateString()]) }}</x-badge></x-field>
                @endif
            </dl>
        </x-card>

        <x-card :title="__('amis.contract')">
            <x-slot name="actions">
                <x-link-button variant="view" :href="route('amis-contracts.show', $contract)">{{ __('app.view') }}</x-link-button>
            </x-slot>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-field :label="__('amis.fields.contract_number')"><span class="font-mono">{{ $contract->contract_number }}</span></x-field>
                <x-field :label="__('amis.fields.contract_name')">{{ $contract->contract_name }}</x-field>
                <x-field :label="__('amis.fields.companies')">{{ $contract->companies->isNotEmpty() ? $contract->companies->pluck('name_lao')->filter()->implode(', ') : implode(', ', $contract->partner_names) }}</x-field>
                <x-field :label="__('amis.fields.location')">{{ $contract->items->map(fn ($i) => collect([$provinces[$i->province_id] ?? null, $i->district_name])->filter()->implode(' · '))->unique()->implode('; ') }}</x-field>
                <x-field :label="__('amis.fields.area')">@if ($contract->area_ha !== null)<x-hectares :value="$contract->area_ha" />@endif</x-field>
                @if ($contract->isMissingFromSource())
                    <x-field :label="__('app.filters.source')"><x-badge tone="red">{{ __('app.missing_since', ['date' => $contract->missing_since->toDateString()]) }}</x-badge></x-field>
                @endif
            </dl>
        </x-card>
    </div>

    <x-card :title="__('matching.score')">
        <x-match-score :card="$score" class="sm:w-64" />
        <x-rule-outcomes :card="$score" class="mt-4" />
    </x-card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card :title="__('matches.match')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-field :label="__('matches.fields.proposed_by')">{{ __('matches.fields.by_at', ['name' => $match->proposer->name, 'date' => $match->created_at->toDateTimeString()]) }}</x-field>
                <x-field :label="__('matches.fields.matched_at')">{{ $match->matched_at?->toDateTimeString() }}</x-field>
                <x-field :label="__('matches.fields.lcis_confirmation')">{{ $confirmation($match->lcisConfirmer, $match->lcis_confirmed_at) ?? __('matches.fields.waiting') }}</x-field>
                <x-field :label="__('matches.fields.amis_confirmation')">{{ $confirmation($match->amisConfirmer, $match->amis_confirmed_at) ?? __('matches.fields.waiting') }}</x-field>
                @if ($match->rejection_reason)
                    <x-field :label="__('matches.fields.rejection_reason')" class="sm:col-span-2">{{ $match->rejection_reason }}</x-field>
                @endif
                @if ($match->unmatch_reason)
                    <x-field :label="__('matches.fields.unmatch_reason')" class="sm:col-span-2">{{ $match->unmatch_reason }}</x-field>
                @endif
            </dl>

            @include('matches.partials.actions')
        </x-card>

        <x-card :title="__('matches.history')">
            <ol class="relative border-s border-blue-gray-100 ms-2">
                @foreach ($match->events as $event)
                    <li class="mb-5 ms-4 last:mb-0">
                        <span class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-blue-gray-300"></span>
                        <p class="text-sm font-medium text-blue-gray-900">
                            {{ $event->action->label() }}
                            @if ($event->from_status && $event->from_status !== $event->to_status)
                                <span class="font-normal text-gray-500">· {{ $event->from_status->label() }} → {{ $event->to_status->label() }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $event->user?->name ?? __('matches.fields.system') }}@if ($event->side) ({{ $event->side->label() }})@endif · {{ $event->created_at->toDateTimeString() }}
                        </p>
                        @if ($event->reason)
                            <p class="mt-1 text-sm text-blue-gray-900 break-words">{{ $event->reason }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>
</x-app-layout>
