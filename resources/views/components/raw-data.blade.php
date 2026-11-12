@props(['raw'])

<x-card>
    <details class="group">
        <summary class="cursor-pointer select-none text-sm font-semibold text-blue-gray-900">{{ __('app.raw_data') }}</summary>
        <pre class="mt-4 max-h-[32rem] overflow-auto rounded-lg bg-blue-gray-50/60 p-4 text-xs leading-relaxed text-blue-gray-900">{{ json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </details>
</x-card>
