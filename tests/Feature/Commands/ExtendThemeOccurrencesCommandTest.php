<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ExtendThemeOccurrencesCommandTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = storage_path('framework/testing/themes-'.uniqid().'.json');
    }

    protected function tearDown(): void
    {
        File::delete($this->path);

        parent::tearDown();
    }

    public function test_fixed_keeps_month_day_and_duration(): void
    {
        $this->writeFile(
            [$this->theme('kerstavond', 'fixed', 'Fixed: month-day 12-24'), $this->theme('voorleesweek', 'fixed', 'Fixed: month-day 11-20')],
            ['occurrences_2026' => [
                $this->occurrence('kerstavond', 2026, '2026-12-24'),
                $this->occurrence('voorleesweek', 2026, '2026-11-20', '2026-11-28'),
            ]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->assertSuccessful();

        $this->assertSame(
            ['theme_slug' => 'kerstavond', 'year' => 2027, 'start_date' => '2027-12-24', 'end_date' => null],
            $this->occurrenceIn(2027, 'kerstavond'),
        );
        $this->assertSame(
            ['theme_slug' => 'voorleesweek', 'year' => 2027, 'start_date' => '2027-11-20', 'end_date' => '2027-11-28'],
            $this->occurrenceIn(2027, 'voorleesweek'),
        );
    }

    public function test_nth_weekday_resolves_both_known_forms(): void
    {
        $this->writeFile(
            [
                $this->theme('vaderdag', 'nth_weekday', '2nd Sunday of June'),
                $this->theme('winteruur', 'nth_weekday', 'Last Sunday of October'),
            ],
            ['occurrences_2026' => [
                $this->occurrence('vaderdag', 2026, '2026-06-14'),
                $this->occurrence('winteruur', 2026, '2026-10-25'),
            ]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->assertSuccessful();

        $this->assertSame('2027-06-13', $this->occurrenceIn(2027, 'vaderdag')['start_date']);
        $this->assertSame('2027-10-31', $this->occurrenceIn(2027, 'winteruur')['start_date']);
    }

    public function test_easter_adds_the_offset_to_easter_sunday(): void
    {
        $this->writeFile(
            [
                $this->theme('onze-lieve-heer-hemelvaart', 'easter', 'Easter + 39 days (Thu)'),
                $this->theme('pinksteren', 'easter', 'Easter + 49 days'),
            ],
            ['occurrences_2026' => [
                $this->occurrence('onze-lieve-heer-hemelvaart', 2026, '2026-05-14'),
                $this->occurrence('pinksteren', 2026, '2026-05-24'),
            ]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->assertSuccessful();

        $this->assertSame('2027-05-06', $this->occurrenceIn(2027, 'onze-lieve-heer-hemelvaart')['start_date']);
        $this->assertSame('2027-05-16', $this->occurrenceIn(2027, 'pinksteren')['start_date']);
    }

    public function test_duration_is_counted_in_days_across_a_leap_day(): void
    {
        $this->writeFile(
            [$this->theme('carnaval', 'fixed', 'Fixed: month-day 02-27')],
            ['occurrences_2027' => [$this->occurrence('carnaval', 2027, '2027-02-27', '2027-03-01')]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2028, '--file' => $this->path])
            ->assertSuccessful();

        $this->assertSame(
            ['theme_slug' => 'carnaval', 'year' => 2028, 'start_date' => '2028-02-27', 'end_date' => '2028-02-29'],
            $this->occurrenceIn(2028, 'carnaval'),
        );
    }

    public function test_a_leap_day_reference_fails_in_a_year_without_one(): void
    {
        $this->writeFile(
            [$this->theme('schrikkeldag', 'fixed', 'Fixed: month-day 02-29')],
            ['occurrences_2028' => [$this->occurrence('schrikkeldag', 2028, '2028-02-29')]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2029, '--file' => $this->path])
            ->expectsOutputToContain('schrikkeldag')
            ->assertFailed();

        $this->assertArrayNotHasKey('occurrences_2029', $this->data());
    }

    public function test_an_unparseable_nth_weekday_detail_fails_without_writing(): void
    {
        $this->writeFile(
            [$this->theme('vaderdag', 'nth_weekday', 'Second Sunday in June')],
            ['occurrences_2026' => [$this->occurrence('vaderdag', 2026, '2026-06-14')]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->expectsOutputToContain('Second Sunday in June')
            ->assertFailed();

        $this->assertArrayNotHasKey('occurrences_2027', $this->data());
    }

    public function test_an_unparseable_easter_detail_fails_without_writing(): void
    {
        $this->writeFile(
            [$this->theme('pinksteren', 'easter', 'Seven weeks after Easter')],
            ['occurrences_2026' => [$this->occurrence('pinksteren', 2026, '2026-05-24')]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->expectsOutputToContain('Seven weeks after Easter')
            ->assertFailed();

        $this->assertArrayNotHasKey('occurrences_2027', $this->data());
    }

    public function test_manual_rules_are_reported_with_last_years_dates_and_not_invented(): void
    {
        $this->writeFile(
            [
                $this->theme('ronde-van-frankrijk', 'variable_annual', 'Tour de France ~first 3 weeks of July'),
                $this->theme('herfstvakantie', 'school_calendar', 'Flemish school calendar (set by gov)'),
                $this->theme('kerstavond', 'fixed', 'Fixed: month-day 12-24'),
            ],
            ['occurrences_2026' => [
                $this->occurrence('ronde-van-frankrijk', 2026, '2026-07-04', '2026-07-26'),
                $this->occurrence('herfstvakantie', 2026, '2026-10-31', '2026-11-08'),
                $this->occurrence('kerstavond', 2026, '2026-12-24'),
            ]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->expectsOutputToContain('ronde-van-frankrijk (variable_annual): 2026-07-04 tot 2026-07-26')
            ->expectsOutputToContain('herfstvakantie (school_calendar): 2026-10-31 tot 2026-11-08')
            ->assertSuccessful();

        $slugs = array_column($this->data()['occurrences_2027'], 'theme_slug');

        $this->assertSame(['kerstavond'], $slugs);
    }

    public function test_the_most_recent_occurrence_is_the_reference(): void
    {
        $this->writeFile(
            [$this->theme('open-monumentendag', 'fixed', 'Fixed: month-day 09-14')],
            [
                'occurrences_2025' => [$this->occurrence('open-monumentendag', 2025, '2025-09-13')],
                'occurrences_2026' => [$this->occurrence('open-monumentendag', 2026, '2026-09-14', '2026-09-15')],
            ],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->assertSuccessful();

        $this->assertSame(
            ['theme_slug' => 'open-monumentendag', 'year' => 2027, 'start_date' => '2027-09-14', 'end_date' => '2027-09-15'],
            $this->occurrenceIn(2027, 'open-monumentendag'),
        );
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->writeFile(
            [$this->theme('kerstavond', 'fixed', 'Fixed: month-day 12-24')],
            ['occurrences_2026' => [$this->occurrence('kerstavond', 2026, '2026-12-24')]],
        );

        $before = File::get($this->path);

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path, '--dry-run' => true])
            ->expectsOutputToContain('2027-12-24')
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();

        $this->assertSame($before, File::get($this->path));
    }

    public function test_an_existing_block_is_only_overwritten_with_force(): void
    {
        $this->writeFile(
            [$this->theme('kerstavond', 'fixed', 'Fixed: month-day 12-24')],
            [
                'occurrences_2026' => [$this->occurrence('kerstavond', 2026, '2026-12-24')],
                'occurrences_2027' => [$this->occurrence('kerstavond', 2027, '2027-01-01')],
            ],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->expectsOutputToContain('--force')
            ->assertFailed();

        $this->assertSame('2027-01-01', $this->occurrenceIn(2027, 'kerstavond')['start_date']);

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path, '--force' => true])
            ->assertSuccessful();

        $this->assertSame('2027-12-24', $this->occurrenceIn(2027, 'kerstavond')['start_date']);
        $this->assertCount(1, $this->data()['occurrences_2027']);
    }

    public function test_it_writes_with_two_space_indentation(): void
    {
        $this->writeFile(
            [$this->theme('kerstavond', 'fixed', 'Fixed: month-day 12-24')],
            ['occurrences_2026' => [$this->occurrence('kerstavond', 2026, '2026-12-24')]],
        );

        $this->artisan('themes:extend-occurrences', ['--year' => 2027, '--file' => $this->path])
            ->assertSuccessful();

        $json = File::get($this->path);

        $this->assertStringContainsString("\n  \"occurrences_2027\": [\n    {\n      \"theme_slug\": \"kerstavond\",", $json);
        $this->assertStringEndsWith("}\n", $json);
    }

    public function test_it_requires_a_year(): void
    {
        $this->writeFile(
            [$this->theme('kerstavond', 'fixed', 'Fixed: month-day 12-24')],
            ['occurrences_2026' => [$this->occurrence('kerstavond', 2026, '2026-12-24')]],
        );

        $this->artisan('themes:extend-occurrences', ['--file' => $this->path])
            ->assertFailed();

        $this->assertArrayNotHasKey('occurrences_2027', $this->data());
    }

    /**
     * @return array<string, mixed>
     */
    private function theme(string $slug, string $rule, string $detail): array
    {
        return [
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => 'Een thema.',
            'is_month' => false,
            'recurrence_rule' => $rule,
            'recurrence_detail' => $detail,
            'fiche_slugs' => [],
        ];
    }

    /**
     * @return array{theme_slug: string, year: int, start_date: string, end_date: string|null}
     */
    private function occurrence(string $slug, int $year, string $start, ?string $end = null): array
    {
        return ['theme_slug' => $slug, 'year' => $year, 'start_date' => $start, 'end_date' => $end];
    }

    /**
     * @param  list<array<string, mixed>>  $themes
     * @param  array<string, list<array<string, mixed>>>  $occurrenceBlocks
     */
    private function writeFile(array $themes, array $occurrenceBlocks): void
    {
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode(
            array_merge(['themes' => $themes], $occurrenceBlocks),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )."\n");
    }

    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        return json_decode(File::get($this->path), true);
    }

    /**
     * @return array<string, mixed>
     */
    private function occurrenceIn(int $year, string $slug): array
    {
        foreach ($this->data()["occurrences_{$year}"] ?? [] as $occurrence) {
            if ($occurrence['theme_slug'] === $slug) {
                return $occurrence;
            }
        }

        $this->fail("Occurrence {$year} voor {$slug} niet gevonden in het bestand.");
    }
}
