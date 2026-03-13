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

    public function withPreviews(int $count = 3, ?int $totalSlides = null): static
    {
        return $this->state(fn () => [
            'preview_images' => collect(range(1, $count))
                ->map(fn ($i) => 'file-previews/'.fake()->randomNumber(3).'/slide-'.str_pad($i, 3, '0', STR_PAD_LEFT).'.jpg')
                ->all(),
            'total_slides' => $totalSlides ?? $count,
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

    public function docx(): static
    {
        return $this->state(fn () => [
            'original_filename' => fake()->word().'-document.docx',
            'path' => 'files/'.fake()->uuid().'.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function generatedPdf(\App\Models\File $sourceFile): static
    {
        return $this->state(fn () => [
            'source_file_id' => $sourceFile->id,
            'fiche_id' => $sourceFile->fiche_id,
            'original_filename' => pathinfo($sourceFile->original_filename, PATHINFO_FILENAME).'.pdf',
            'path' => 'files/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
