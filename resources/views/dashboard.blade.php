<x-app-layout>
    <x-slot name="title">{{ __('app.nav.dashboard') }}</x-slot>
    <x-slot name="header">
        <x-page-title>{{ __('app.nav.dashboard') }}</x-page-title>
    </x-slot>

    <x-card>
        <p class="text-sm">{{ __('app.dashboard_placeholder') }}</p>
    </x-card>
</x-app-layout>
