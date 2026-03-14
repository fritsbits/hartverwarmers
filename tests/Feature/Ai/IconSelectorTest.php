<?php

namespace Tests\Feature\Ai;

use App\Ai\Agents\IconSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IconSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_selects_icon_for_music_activity(): void
    {
        IconSelector::fake(fn (string $prompt) => 'music');

        $response = (new IconSelector)->prompt('Eurosong quiz');

        $this->assertEquals('music', (string) $response);
    }

    public function test_selects_fallback_for_unknown_activity(): void
    {
        IconSelector::fake(fn (string $prompt) => 'file-text');

        $response = (new IconSelector)->prompt('Algemene activiteit');

        $this->assertEquals('file-text', (string) $response);
    }

    public function test_instructions_contain_allowlist(): void
    {
        $agent = new IconSelector;
        $instructions = $agent->instructions();

        $this->assertStringContainsString('music', $instructions);
        $this->assertStringContainsString('flower-2', $instructions);
        $this->assertStringContainsString('file-text', $instructions);
    }
}
