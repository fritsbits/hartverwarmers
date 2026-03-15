<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[UseCheapestModel]
#[MaxTokens(1024)]
#[Temperature(0)]
class IconSelector implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $icons = implode(', ', config('fiche-icons.allowlist'));

        return <<<PROMPT
        You are an icon selector for a Dutch elderly care activities platform.
        Given an activity title (and its parent initiative name for context), pick the single most representative icon name from this list:

        {$icons}

        Rules:
        - Return ONLY the icon name, nothing else. No quotes, no explanation.
        - If no icon fits well, return "file-text".
        - The activity titles are in Dutch. Common themes: music, crafts, cooking, nature, games, holidays, movement, memory exercises.
        - IMPORTANT: Focus on what makes this specific activity UNIQUE, not the overall initiative theme. If the initiative is "Muziek maken", don't pick "music" for every fiche — instead pick an icon for the distinctive aspect (e.g. "Belevingsmoment lente" → flower-2, "Kerst beweegparcour" → snowflake). The initiative name already tells users the theme.
        PROMPT;
    }
}
