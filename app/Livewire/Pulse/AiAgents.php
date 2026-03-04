<?php

namespace App\Livewire\Pulse;

use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class AiAgents extends Card
{
    public function render()
    {
        $calls = $this->aggregate('ai_agent_call', ['sum', 'count']);
        $tokens = $this->aggregate('ai_agent_tokens', ['sum']);
        $durations = $this->aggregate('ai_agent_duration', ['avg', 'max']);

        $agents = $calls->map(function ($call) use ($tokens, $durations) {
            $tokenData = $tokens->firstWhere('key', $call->key);
            $durationData = $durations->firstWhere('key', $call->key);

            return (object) [
                'name' => $call->key,
                'calls' => (int) $call->count,
                'total_cost' => round((float) $call->sum, 4),
                'total_tokens' => (int) ($tokenData->sum ?? 0),
                'avg_duration_ms' => round((float) ($durationData->avg ?? 0)),
                'max_duration_ms' => round((float) ($durationData->max ?? 0)),
            ];
        });

        $totals = (object) [
            'calls' => $agents->sum('calls'),
            'total_cost' => round($agents->sum('total_cost'), 4),
            'total_tokens' => $agents->sum('total_tokens'),
        ];

        return view('livewire.pulse.ai-agents', [
            'agents' => $agents,
            'totals' => $totals,
        ]);
    }
}
