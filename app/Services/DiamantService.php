<?php

namespace App\Services;

class DiamantService
{
    /**
     * @return array<string, array{letter: string, keyword: string, slug: string, tagline: string, quote: string, ik_wil: string, description: string, author_name: string, author_role: string, author_image: string, challenges: string[], core_question: string, contrast_positive: string, contrast_negative: string, practice_subtitle: string, practice_examples: array<array{name: string, role: string, image: ?string, story: string}>, reflection_subtitle: string, reflection_questions: string[], tip_title: string, tip_text: string, adaptations_question: string, adaptations: array<array{activity: string, adaptation: string}>, related_facets_text: string, initiatives_heading: string}>
     */
    public function all(): array
    {
        return config('diamant.facets');
    }

    /**
     * @return array{letter: string, keyword: string, slug: string, tagline: string, quote: string, ik_wil: string, description: string, author_name: string, author_role: string, author_image: string, challenges: string[], core_question: string, contrast_positive: string, contrast_negative: string, practice_subtitle: string, practice_examples: array<array{name: string, role: string, image: ?string, story: string}>, reflection_subtitle: string, reflection_questions: string[], tip_title: string, tip_text: string, adaptations_question: string, adaptations: array<array{activity: string, adaptation: string}>, related_facets_text: string, initiatives_heading: string}|null
     */
    public function findBySlug(string $slug): ?array
    {
        return config('diamant.facets')[$slug] ?? null;
    }

    /**
     * @return array{letter: string, keyword: string, slug: string, tagline: string, quote: string, ik_wil: string, description: string, author_name: string, author_role: string, author_image: string, challenges: string[], core_question: string, contrast_positive: string, contrast_negative: string, practice_subtitle: string, practice_examples: array<array{name: string, role: string, image: ?string, story: string}>, reflection_subtitle: string, reflection_questions: string[], tip_title: string, tip_text: string, adaptations_question: string, adaptations: array<array{activity: string, adaptation: string}>, related_facets_text: string, initiatives_heading: string}|null
     */
    public function find(string $letter): ?array
    {
        $letter = strtoupper($letter);

        foreach (config('diamant.facets') as $facet) {
            if ($facet['letter'] === $letter) {
                return $facet;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getChallenges(string $slug): array
    {
        return $this->findBySlug($slug)['challenges'] ?? [];
    }
}
