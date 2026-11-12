@props(['label'])

<div {{ $attributes }}>
    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</dt>
    <dd class="mt-1 text-sm text-blue-gray-900 break-words">
        @if (trim((string) $slot) === '')
            <span class="text-gray-400">{{ __('app.not_available') }}</span>
        @else
            {{ $slot }}
        @endif
    </dd>
</div>
