@props(['messages'])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'mt-1 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <p class="mt-1 text-sm font-medium text-red-500">{{ $message }}</p>
        @endforeach
    </div>
@endif
