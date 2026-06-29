<?php

namespace Database\Factories;

use App\Models\EmailBounce;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailBounce>
 */
class EmailBounceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'type' => 'bounce',
            'reason' => 'The recipient address does not exist.',
            'bounced_at' => now(),
        ];
    }

    public function complaint(): static
    {
        return $this->state(fn (): array => ['type' => 'complaint', 'reason' => null]);
    }
}
