<?php

namespace Tests\Unit;

use App\Services\DiamantService;
use Tests\TestCase;

class DiamantServiceTest extends TestCase
{
    private DiamantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DiamantService;
    }

    public function test_all_returns_seven_facets(): void
    {
        $facets = $this->service->all();

        $this->assertCount(7, $facets);
    }

    public function test_find_by_slug_returns_correct_facet(): void
    {
        $facet = $this->service->findBySlug('talent');

        $this->assertNotNull($facet);
        $this->assertEquals('Talent', $facet['keyword']);
        $this->assertEquals('T', $facet['letter']);
    }

    public function test_find_by_slug_returns_null_for_invalid(): void
    {
        $facet = $this->service->findBySlug('nonexistent');

        $this->assertNull($facet);
    }

    public function test_find_by_letter_works(): void
    {
        $facet = $this->service->find('D');

        $this->assertNotNull($facet);
        $this->assertEquals('Doen', $facet['keyword']);
    }

    public function test_find_by_letter_is_case_insensitive(): void
    {
        $facet = $this->service->find('d');

        $this->assertNotNull($facet);
        $this->assertEquals('Doen', $facet['keyword']);
    }

    public function test_find_by_letter_returns_null_for_invalid(): void
    {
        $facet = $this->service->find('Z');

        $this->assertNull($facet);
    }

    public function test_get_challenges_returns_array(): void
    {
        $challenges = $this->service->getChallenges('doen');

        $this->assertIsArray($challenges);
        $this->assertNotEmpty($challenges);
    }

    public function test_get_challenges_returns_empty_for_invalid(): void
    {
        $challenges = $this->service->getChallenges('nonexistent');

        $this->assertIsArray($challenges);
        $this->assertEmpty($challenges);
    }

    public function test_each_facet_has_required_keys(): void
    {
        $requiredKeys = ['letter', 'keyword', 'slug', 'tagline', 'quote', 'ik_wil', 'description', 'challenges', 'practice_examples', 'reflection_questions'];

        foreach ($this->service->all() as $slug => $facet) {
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $facet, "Facet '{$slug}' is missing key '{$key}'");
            }
        }
    }
}
