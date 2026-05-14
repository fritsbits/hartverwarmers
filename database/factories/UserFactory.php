<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'contributor',
            'organisation' => fake()->company(),
            'function_title' => fake()->jobTitle(),
            'bio' => fake()->optional()->paragraph(),
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
            'notification_frequency' => 'weekly',
            'notify_on_onboarding_emails' => true,
            'notify_on_kudos_milestones' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function contributor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'contributor',
        ]);
    }

    public function curator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'curator',
        ]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'member',
        ]);
    }
}
