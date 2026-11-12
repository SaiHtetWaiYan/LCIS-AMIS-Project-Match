<x-app-layout>
    <x-slot name="title">{{ __('users.create') }}</x-slot>

    <x-card :title="__('users.create')">
        <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-xl">
            @csrf
            @include('admin.users.partials.form', ['user' => null])
        </form>
    </x-card>
</x-app-layout>
