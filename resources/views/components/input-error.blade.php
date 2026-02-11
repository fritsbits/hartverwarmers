@props(['messages'])

@if ($messages)
    <div {{ $attributes->merge(['class' => 'mt-1 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <flux:error>{{ $message }}</flux:error>
        @endforeach
    </div>
@endif
