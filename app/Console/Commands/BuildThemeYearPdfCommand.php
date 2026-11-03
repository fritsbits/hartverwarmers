<?php

namespace App\Console\Commands;

use App\Services\ThemeCalendar;
use Carbon\CarbonImmutable;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Bouwt de gratis jaarkalender als één PDF: een cover, een gebruiksblad en
 * twaalf A3-maandbladen uit dezelfde partial als /themas/print. Draait
 * lokaal met headless Chrome; de PDF gaat mee in git naar public/downloads.
 * De QR-encoder is een dev-dependency, dus dit commando draait niet in productie.
 */
class BuildThemeYearPdfCommand extends Command
{
    public const SHARE_PATH = '/jaarkalender';

    protected $signature = 'themes:year-pdf
        {year? : Kalenderjaar, standaard volgend jaar}
        {--base-url=https://hartverwarmers.be : Domein voor links en QR-codes}
        {--html : Schrijf alleen de HTML weg, zonder PDF}
        {--chrome= : Pad naar Chrome of Chromium}';

    protected $description = 'Bouw de A3-jaarkalender als PDF in public/downloads';

    public function handle(ThemeCalendar $calendar): int
    {
        $year = (int) ($this->argument('year') ?? CarbonImmutable::now('Europe/Brussels')->addYear()->year);
        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);

        $sheets = collect(range(1, 12))->map(function (int $monthNumber) use ($calendar, $year, $baseUrl, $host) {
            $month = CarbonImmutable::create($year, $monthNumber, 1, 0, 0, 0, 'Europe/Brussels');
            $path = '/themas?maand='.$month->format('Y-m');

            return $calendar->printSheet($month) + [
                'monthLink' => [
                    'url' => $baseUrl.$path,
                    'label' => $host.$path,
                    'qr' => $this->qrSvg($baseUrl.$path),
                ],
            ];
        });

        $html = view('themes.year-print', [
            'year' => $year,
            'sheets' => $sheets,
            'themesUrl' => $baseUrl.'/themas',
            'shareUrl' => $baseUrl.self::SHARE_PATH,
            'shareLabel' => $host.self::SHARE_PATH,
        ])->render();

        $directory = public_path('downloads');
        File::ensureDirectoryExists($directory);
        $basename = "hartverwarmers-themakalender-{$year}";

        if ($this->option('html')) {
            File::put("{$directory}/{$basename}.html", $html);
            $this->info("HTML geschreven: public/downloads/{$basename}.html");

            return Command::SUCCESS;
        }

        $chrome = $this->chromeBinary();
        if ($chrome === null) {
            $this->error('Geen Chrome gevonden. Geef het pad mee met --chrome=.');

            return Command::FAILURE;
        }

        $source = storage_path("app/{$basename}.html");
        File::put($source, $html);
        $target = "{$directory}/{$basename}.pdf";

        $result = Process::timeout(120)->run([
            $chrome,
            '--headless',
            '--disable-gpu',
            '--no-pdf-header-footer',
            '--virtual-time-budget=15000',
            '--print-to-pdf='.$target,
            'file://'.$source,
        ]);

        File::delete($source);

        if ($result->failed() || ! File::exists($target)) {
            $this->error('Chrome kon de PDF niet maken.');
            $this->line($result->errorOutput());

            return Command::FAILURE;
        }

        $this->info("PDF geschreven: public/downloads/{$basename}.pdf");

        return Command::SUCCESS;
    }

    private function qrSvg(string $url): string
    {
        return (new QRCode(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'addQuietzone' => false,
            'drawLightModules' => false,
            'connectPaths' => true,
        ])))->render($url);
    }

    private function chromeBinary(): ?string
    {
        $candidates = array_filter([
            $this->option('chrome'),
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
        ]);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
