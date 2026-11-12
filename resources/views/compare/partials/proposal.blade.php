{{-- Propose the compared pair, or say why the current user cannot. Admins do not propose. --}}
@php
    $proposal = Gate::inspect('create', [App\Models\ProjectMatch::class, $project, $contract]);
@endphp

@if (auth()->user()->side !== App\Enums\UserSide::Admin)
    <x-card :title="__('compare.proposal.title')" :subtitle="$proposal->allowed() ? __('compare.proposal.hint') : null">
        @if ($proposal->allowed())
            <form method="POST" action="{{ route('matches.store') }}">
                @csrf
                <input type="hidden" name="lcis_project_id" value="{{ $project->id }}">
                <input type="hidden" name="amis_contract_id" value="{{ $contract->id }}">
                <button type="submit" class="inline-flex items-center rounded-lg bg-gray-700 py-2 px-4 text-xs font-bold uppercase text-white shadow-md hover:bg-gray-900">{{ __('matches.buttons.propose') }}</button>
            </form>
        @else
            <p class="text-sm text-gray-600">{{ __('compare.proposal.refused', ['reason' => $proposal->message()]) }}</p>
        @endif
    </x-card>
@endif
