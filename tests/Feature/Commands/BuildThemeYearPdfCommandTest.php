<?php

namespace Tests\Feature\Commands;

use App\Models\Theme;
use App\Models\ThemeOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BuildThemeYearPdfCommandTest extends TestCase
{
    use RefreshDatabase;

    private const YEAR = 2099;

    protected function tearDown(): void
    {
        File::delete([
            public_path('downloads/hartverwarmers-themakalender-'.self::YEAR.'.html'),
            public_path('downloads/hartverwarmers-themakalender-'.self::YEAR.'.pdf'),
        ]);

        parent::tearDown();
    }

    private function htmlPath(): string
    {
        return public_path('downloads/hartverwarmers-themakalender-'.self::YEAR.'.html');
    }

    public function test_html_holds_cover_guide_and_twelve_month_sheets(): void
    {
        $this->artisan('themes:year-pdf', ['year' => self::YEAR, '--html' => true])->assertSuccessful();

        $html = File::get($this->htmlPath());

        $this->assertStringContainsString('Zo haal je het meeste uit deze kalender', $html);
        $this->assertSame(14, preg_match_all('/class="sheet[ "]/', $html));
        foreach (['januari', 'juni', 'december'] as $month) {
            $this->assertStringContainsString($month.' '.self::YEAR, $html);
        }
    }

    public function test_every_month_links_to_its_public_theme_page_with_a_qr_code(): void
    {
        $this->artisan('themes:year-pdf', ['year' => self::YEAR, '--html' => true])->assertSuccessful();

        $html = File::get($this->htmlPath());

        $this->assertSame(12, substr_count($html, '<svg xmlns="http://www.w3.org/2000/svg" class="qr-svg'));
        $this->assertStringContainsString('href="https://hartverwarmers.be/themas?maand='.self::YEAR.'-03"', $html);
        $this->assertStringContainsString('href="https://hartverwarmers.be/jaarkalender"', $html);
        $this->assertStringNotContainsString('.test', $html);
    }

    public function test_base_url_option_rewrites_links(): void
    {
        $this->artisan('themes:year-pdf', ['year' => self::YEAR, '--html' => true, '--base-url' => 'https://voorbeeld.be/'])->assertSuccessful();

        $this->assertStringContainsString('href="https://voorbeeld.be/themas?maand='.self::YEAR.'-01"', File::get($this->htmlPath()));
    }

    public function test_every_theme_of_a_crowded_day_keeps_its_title(): void
    {
        foreach (['Dag van de koffie', 'Dag van de ouderen', 'Muziekdag', 'Veggiedag', 'Tiendaagse'] as $title) {
            $theme = Theme::factory()->create(['title' => $title, 'description' => "Idee bij {$title}."]);
            ThemeOccurrence::factory()->for($theme)->create(['year' => self::YEAR, 'start_date' => self::YEAR.'-10-01']);
        }

        $this->artisan('themes:year-pdf', ['year' => self::YEAR, '--html' => true])->assertSuccessful();

        $html = File::get($this->htmlPath());

        $this->assertStringContainsString('is-crowded', $html);
        foreach (['Dag van de koffie', 'Dag van de ouderen', 'Muziekdag', 'Veggiedag', 'Tiendaagse'] as $title) {
            $this->assertStringContainsString($title, $html);
        }
        $this->assertStringNotContainsString('Idee bij Muziekdag.', $html);
    }

    public function test_prints_the_html_to_pdf_with_headless_chrome(): void
    {
        $target = public_path('downloads/hartverwarmers-themakalender-'.self::YEAR.'.pdf');

        Process::fake(function (PendingProcess $process) use ($target) {
            File::put($target, '%PDF-1.4');

            return Process::result();
        });

        $this->artisan('themes:year-pdf', ['year' => self::YEAR, '--chrome' => PHP_BINARY])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $process) => in_array('--print-to-pdf='.$target, $process->command, true)
            && in_array('--no-pdf-header-footer', $process->command, true));
        $this->assertFileExists($target);
    }

    public function test_fails_when_chrome_produces_no_pdf(): void
    {
        Process::fake(fn () => Process::result(errorOutput: 'crash', exitCode: 1));

        $this->artisan('themes:year-pdf', ['year' => self::YEAR, '--chrome' => PHP_BINARY])->assertFailed();
    }
}
