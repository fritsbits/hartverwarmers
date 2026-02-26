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
}
