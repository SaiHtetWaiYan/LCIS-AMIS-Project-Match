@props(['tone' => 'gray'])

@php
    $tones = [
        'gray' => 'bg-blue-gray-50 text-blue-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'green' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold '.($tones[$tone] ?? $tones['gray'])]) }}>
    {{ $slot }}
</span>
