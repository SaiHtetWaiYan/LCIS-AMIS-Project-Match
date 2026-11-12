@props(['variant' => 'primary'])

@php
    $variants = [
        'primary' => 'bg-gray-700 text-white shadow-md hover:bg-gray-900 hover:shadow-lg',
        'edit' => 'bg-blue-600 text-white shadow-md hover:bg-blue-700 normal-case font-normal text-sm',
        'view' => 'border border-green-600 bg-white text-green-700 shadow-sm hover:bg-green-50 normal-case font-normal text-sm',
    ];
@endphp

<a {{ $attributes->merge(['class' => 'inline-flex select-none items-center gap-2 rounded-lg py-2 px-4 text-center align-middle font-sans text-xs font-bold uppercase transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 '.($variants[$variant] ?? $variants['primary'])]) }}>
    {{ $slot }}
</a>
