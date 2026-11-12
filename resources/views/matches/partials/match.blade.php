{{-- Status, confirmations, the actions the current user may take, and the history of a match. --}}
@php
    $confirmation = fn ($user, $at) => $at ? __('matches.fields.by_at', ['name' => $user?->name ?? '—', 'date' => $at->toDateTimeString()]) : null;
@endphp

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
