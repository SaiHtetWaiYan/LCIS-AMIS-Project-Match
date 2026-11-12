@php
    $isEdit = $user !== null;
    $canChangeSide = ! $isEdit || auth()->user()->can('changeSide', $user);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('users.fields.name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user?->name)" required autofocus autocomplete="off" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="email" :value="__('users.fields.email')" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user?->email)" required autocomplete="off" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="side" :value="__('users.fields.side')" />
        @if ($canChangeSide)
            <x-select-input id="side" name="side" class="mt-1 block w-full" required>
                <option value="" disabled @selected(old('side', $user?->side?->value) === null)>—</option>
                @foreach (App\Enums\UserSide::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('side', $user?->side?->value) === $value)>{{ $label }}</option>
                @endforeach
            </x-select-input>
        @else
            <p id="side" class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->side->label() }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('users.hints.own_side') }}</p>
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('side')" />
    </div>

    <div>
        <x-input-label for="password" :value="__('users.fields.password')" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $isEdit" autocomplete="new-password" />
        @if ($isEdit)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('users.hints.password_keep') }}</p>
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('password')" />
    </div>

    <div>
        <x-input-label for="password_confirmation" :value="__('users.fields.password_confirmation')" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="! $isEdit" autocomplete="new-password" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ __('users.actions.save') }}</x-primary-button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline">
            {{ __('users.actions.cancel') }}
        </a>
    </div>
</div>
