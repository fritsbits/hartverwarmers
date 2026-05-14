<x-sidebar-layout title="Dashboard" section-label="Beheer">

    <x-slot:header-action>
        <flux:select size="sm" class="w-40" x-data x-on:change="window.location.href = '?tab={{ $tab }}&range=' + $event.target.value">
            <option value="week" {{ $range === 'week' ? 'selected' : '' }}>Laatste week</option>
            <option value="month" {{ $range === 'month' ? 'selected' : '' }}>Laatste maand</option>
            <option value="quarter" {{ $range === 'quarter' ? 'selected' : '' }}>Laatste 3 maanden</option>
            <option value="alltime" {{ $range === 'alltime' ? 'selected' : '' }}>Sinds start</option>
        </flux:select>
    </x-slot:header-action>

    {{-- Tab switcher --}}
    <div x-data="{
        tab: '{{ $tab }}',
        range: '{{ $range }}',
        navigate(val) {
            const validRanges = ['week', 'month', 'quarter', 'alltime'];
            const r = validRanges.includes(this.range) ? this.range : 'month';
            window.location.href = '?tab=' + val + '&range=' + r;
        }
    }" x-init="$watch('tab', val => navigate(val))" class="mb-6">
        <flux:tabs x-model="tab" variant="segmented">
            <flux:tab name="overzicht">Overzicht</flux:tab>
            @foreach($objectives as $obj)
                <flux:tab name="{{ $obj->slug }}">{{ $obj->title }}</flux:tab>
            @endforeach
        </flux:tabs>
    </div>

    @if($tab === 'overzicht')
        @include('admin.tabs.overzicht', ['objectives' => $objectives, 'range' => $range])
    @elseif(view()->exists('admin.tabs.' . $tab))
        @include('admin.tabs.' . $tab)
    @endif

</x-sidebar-layout>
