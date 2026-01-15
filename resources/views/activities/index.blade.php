<x-layout title="Activiteiten">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <!-- Header -->
        <div class="intro-block py-8">
            <h1>Activiteiten</h1>
            <p>Ontdek inspirerende activiteiten voor ouderen, gefilterd op interesse.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-64 shrink-0">
                <div class="sticky top-24 space-y-6">
                    <!-- Interest Filter -->
                    <div>
                        <h3 class="font-semibold mb-4">Filter op interesse</h3>
                        <ul class="menu bg-base-200 rounded-box w-full">
                            <li>
                                <a href="{{ route('activities.index', array_filter(['dimension' => $selectedDimension, 'guidance' => $selectedGuidance])) }}"
                                   class="{{ !$selectedInterest ? 'active' : '' }}">
                                    Alle interesses
                                </a>
                            </li>
                            @foreach($domains as $domain)
                                <li>
                                    <a href="{{ route('activities.index', array_filter(['interest' => $domain->id, 'dimension' => $selectedDimension, 'guidance' => $selectedGuidance])) }}"
                                       class="{{ $selectedInterest == $domain->id ? 'active' : '' }}">
                                        {{ $domain->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Dimension Filter (Sense of Home) -->
                    <div>
                        <h3 class="font-semibold mb-4">Sense of Home</h3>
                        <ul class="menu bg-base-200 rounded-box w-full">
                            <li>
                                <a href="{{ route('activities.index', array_filter(['interest' => $selectedInterest, 'guidance' => $selectedGuidance])) }}"
                                   class="{{ !$selectedDimension ? 'active' : '' }}">
                                    Alle dimensies
                                </a>
                            </li>
                            @foreach($dimensions as $dimension)
                                <li>
                                    <a href="{{ route('activities.index', array_filter(['interest' => $selectedInterest, 'dimension' => $dimension->value, 'guidance' => $selectedGuidance])) }}"
                                       class="{{ $selectedDimension == $dimension->value ? 'active' : '' }}">
                                        {{ $dimension->title() }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Guidance Filter (Zorgprofiel) -->
                    <div>
                        <h3 class="font-semibold mb-4">Zorgprofiel</h3>
                        <ul class="menu bg-base-200 rounded-box w-full">
                            <li>
                                <a href="{{ route('activities.index', array_filter(['interest' => $selectedInterest, 'dimension' => $selectedDimension])) }}"
                                   class="{{ !$selectedGuidance ? 'active' : '' }}">
                                    Alle profielen
                                </a>
                            </li>
                            @foreach($guidances as $guidance)
                                <li>
                                    <a href="{{ route('activities.index', array_filter(['interest' => $selectedInterest, 'dimension' => $selectedDimension, 'guidance' => $guidance->value])) }}"
                                       class="{{ $selectedGuidance == $guidance->value ? 'active' : '' }}"
                                       title="{{ $guidance->description() }}">
                                        {{ $guidance->title() }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </aside>

            <!-- Activities Grid -->
            <div class="flex-1">
                @if($activities->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-base-content/60">Geen activiteiten gevonden.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($activities as $activity)
                            <x-activity-card :activity="$activity" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $activities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout>
