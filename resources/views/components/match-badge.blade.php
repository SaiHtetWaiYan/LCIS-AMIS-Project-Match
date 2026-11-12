@props(['match'])

@if ($match)
    <a href="{{ route('matches.show', $match) }}" class="inline-flex items-center gap-1">
        <x-badge :tone="$match->status->tone()">{{ $match->status->label() }} #{{ $match->id }}</x-badge>
        @if ($match->needs_review)
            <x-badge tone="amber">{{ __('matches.banner.needs_review') }}</x-badge>
        @endif
    </a>
@endif
