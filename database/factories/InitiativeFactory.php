<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Initiative>
 */
class InitiativeFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'content' => fake()->optional()->paragraphs(3, true),
            'published' => false,
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published' => true,
        ]);
    }

    public function withDiamantGuidance(): static
    {
        return $this->state(fn (array $attributes) => [
            'diamant_guidance' => [
                'doen' => ['active' => true, 'description' => 'Bewoners luisteren, raden en overleggen.'],
                'inclusief' => ['active' => true, 'description' => 'Teams helpen elkaar — werkt bij gemengde groepen.'],
                'autonomie' => ['active' => false, 'description' => 'Meestal kiest de begeleider het thema.', 'guidance' => 'Laat bewoners zelf thema\'s of vragen aandragen.'],
                'mensgericht' => ['active' => false, 'description' => 'Standaard quizzen gaan over algemene kennis.', 'guidance' => 'Voeg een ronde toe met persoonlijke vragen.'],
                'anderen' => ['active' => true, 'description' => 'In teams spelen stimuleert overleg.'],
                'normalisatie' => ['active' => false, 'description' => 'Het quizformat kan schools aanvoelen.', 'guidance' => 'Speel de nummers bij de koffie en laat het gesprek vanzelf ontstaan.'],
                'talent' => ['active' => false, 'description' => 'Bewoners beantwoorden, maar brengen geen eigen kennis in.', 'guidance' => 'Wie was muzikant? Laat hen vertellen — of voordoen.'],
            ],
        ]);
    }
}
