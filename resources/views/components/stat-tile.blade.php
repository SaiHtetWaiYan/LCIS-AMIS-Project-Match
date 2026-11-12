@props(['label', 'value', 'hint' => null, 'href' => null, 'highlight' => false])

{{-- A dashboard figure; the whole tile links to the list behind the number. --}}
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group block rounded-xl bg-white p-5 shadow-md transition hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500']) }}>
    <p class="text-sm text-gray-500">{{ $label }}</p>
    <p @class(['mt-2 text-3xl font-semibold tabular-nums', 'text-amber-600' => $highlight, 'text-blue-gray-900' => ! $highlight])>{{ number_format($value) }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</a>
