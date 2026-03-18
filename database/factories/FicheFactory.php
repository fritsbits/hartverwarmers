<?php

namespace Database\Factories;

use App\Models\Initiative;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fiche>
 */
class FicheFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'initiative_id' => Initiative::factory(),
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'practical_tips' => fake()->optional()->paragraph(),
            'materials' => null,
            'target_audience' => null,
            'published' => false,
            'has_diamond' => false,
            'download_count' => 0,
            'kudos_count' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published' => true,
        ]);
    }

    public function withDiamond(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_diamond' => true,
        ]);
    }

    public function ficheOfMonth(?string $month = null): static
    {
        return $this->state(fn () => [
            'featured_month' => $month ?? now()->format('Y-m'),
        ]);
    }

    public function withIcon(string $icon = 'file-text'): static
    {
        return $this->state(fn (array $attributes) => [
            'icon' => $icon,
        ]);
    }

    public function withScores(int $completeness = 100, int $quality = 75): static
    {
        return $this->state(fn () => [
            'completeness_score' => $completeness,
            'quality_score' => $quality,
            'quality_justification' => 'Test justification.',
            'quality_assessed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function withSuggestions(array $overrides = []): static
    {
        return $this->state(fn () => [
            'ai_suggestions' => array_merge([
                'title' => 'Een pakkende titel voor deze activiteit',
                'description' => 'Een heldere en uitnodigende beschrijving',
                'preparation' => 'Stap-voor-stap voorbereiding',
                'inventory' => 'Benodigde materialen',
                'process' => 'De werkwijze uitgelegd',
                'applied' => [],
            ], $overrides),
        ]);
    }

    public function withPresentationScore(int $score): static
    {
        return $this->state(fn () => [
            'presentation_score' => $score,
        ]);
    }
}
