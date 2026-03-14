<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileUpload>
 */
class FileUploadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'file_id' => File::factory(),
            'user_id' => User::factory(),
            'ip_address' => fake()->ipv4(),
            'file_hash' => hash('sha256', fake()->uuid()),
            'original_filename' => fake()->word().'.pdf',
            'disclaimer_accepted_at' => now(),
        ];
    }
}
