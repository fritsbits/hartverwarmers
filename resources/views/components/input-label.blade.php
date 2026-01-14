@props(['value'])

<label {{ $attributes->merge(['class' => 'label']) }}>
    <span class="label-text font-medium">{{ $value ?? $slot }}</span>
</label>
