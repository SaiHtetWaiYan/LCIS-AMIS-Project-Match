{{-- Only the actions the current user may take on this match right now. --}}
@if (auth()->user()->can('confirm', $match) || auth()->user()->can('reject', $match) || auth()->user()->can('withdraw', $match) || auth()->user()->can('unmatch', $match) || auth()->user()->can('review', $match))
    <div class="mt-6 space-y-4 border-t border-blue-gray-50 pt-5">
        <div class="flex flex-wrap items-center gap-3">
            @can('confirm', $match)
                <form method="POST" action="{{ route('matches.confirm', $match) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg bg-green-600 py-2 px-4 text-xs font-bold uppercase text-white shadow-md hover:bg-green-700">{{ __('matches.buttons.confirm') }}</button>
                </form>
            @endcan
            @can('withdraw', $match)
                <form method="POST" action="{{ route('matches.withdraw', $match) }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('matches.buttons.withdraw') }}</x-secondary-button>
                </form>
            @endcan
        </div>

        @foreach (['reject' => 'reason', 'unmatch' => 'reason', 'review' => 'note'] as $action => $field)
            @can($action, $match)
                <form method="POST" action="{{ route('matches.'.$action, $match) }}" class="space-y-2" x-data="{ open: {{ $errors->has($field) ? 'true' : 'false' }} }">
                    @csrf
                    <button type="button" x-show="! open" @click="open = true"
                        @class([
                            'inline-flex items-center rounded-lg py-2 px-4 text-xs font-bold uppercase shadow-md',
                            'bg-red-600 text-white hover:bg-red-700' => $action !== 'review',
                            'bg-amber-500 text-white hover:bg-amber-600' => $action === 'review',
                        ])>{{ __('matches.buttons.'.$action) }}</button>
                    <div x-show="open" x-cloak class="space-y-2">
                        <x-input-label :for="$action.'-'.$field" :value="__($field === 'note' ? 'matches.fields.review_note' : 'matches.fields.reason')" />
                        <x-textarea :id="$action.'-'.$field" :name="$field" class="block w-full" :required="$field === 'reason'" :placeholder="__('matches.reason_placeholder')">{{ old($field) }}</x-textarea>
                        <x-input-error :messages="$errors->get($field)" />
                        <div class="flex gap-3">
                            <x-danger-button>{{ __('matches.buttons.'.$action) }}</x-danger-button>
                            <x-secondary-button @click="open = false">{{ __('users.actions.cancel') }}</x-secondary-button>
                        </div>
                    </div>
                </form>
            @endcan
        @endforeach
    </div>
@endif
