<?php

namespace Database\Factories;

use App\Models\Fiche;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fiche_id' => Fiche::factory(),
            'original_filename' => fake()->word().'.pdf',
            'path' => 'files/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 10485760),
            'sort_order' => 0,
        ];
    }

    public function withPreviews(int $count = 3): static
    {
        return $this->state(fn () => [
            'preview_images' => collect(range(1, $count))
                ->map(fn ($i) => 'file-previews/'.fake()->randomNumber(3).'/slide-'.str_pad($i, 3, '0', STR_PAD_LEFT).'.jpg')
                ->all(),
        ]);
    }

    public function pptx(): static
    {
        return $this->state(fn () => [
            'original_filename' => fake()->word().'-presentatie.pptx',
            'path' => 'files/'.fake()->uuid().'.pptx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }
}
