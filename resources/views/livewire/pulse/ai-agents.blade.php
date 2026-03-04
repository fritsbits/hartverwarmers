<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header name="AI Agents">
        <x-slot:icon>
            <x-pulse::icons.sparkles />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        @if ($agents->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="0%" />
                    <col width="100%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Agent</x-pulse::th>
                        <x-pulse::th class="text-right">Calls</x-pulse::th>
                        <x-pulse::th class="text-right">Tokens</x-pulse::th>
                        <x-pulse::th class="text-right">Avg</x-pulse::th>
                        <x-pulse::th class="text-right">Max</x-pulse::th>
                        <x-pulse::th class="text-right">Cost</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($agents as $agent)
                        <tr class="h-2 first:h-0"></tr>
                        <tr>
                            <x-pulse::td class="max-w-[1px] whitespace-nowrap">
                                <code class="block text-xs text-gray-600 dark:text-gray-300 truncate" title="{{ $agent->name }}">
                                    {{ $agent->name }}
                                </code>
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                {{ number_format($agent->calls) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                {{ number_format($agent->total_tokens) }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                {{ number_format($agent->avg_duration_ms) }}ms
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                {{ number_format($agent->max_duration_ms) }}ms
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                ${{ number_format($agent->total_cost, 4) }}
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>

            <div class="mt-3 flex justify-between text-xs text-gray-500 dark:text-gray-400 px-1">
                <span>{{ number_format($totals->calls) }} total calls</span>
                <span>{{ number_format($totals->total_tokens) }} total tokens</span>
                <span>${{ number_format($totals->total_cost, 4) }} total cost</span>
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
