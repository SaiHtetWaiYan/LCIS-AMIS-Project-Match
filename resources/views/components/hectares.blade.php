@props(['value'])

@if ($value === null)
    <span {{ $attributes->merge(['class' => 'text-gray-400']) }}>{{ __('app.not_available') }}</span>
@else
    <span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ __('app.hectares', ['value' => number_format((float) $value, 2)]) }}</span>
@endif
