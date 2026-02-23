<?php

namespace App\Console\Commands;

use App\Models\Theme;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportThemes extends Command
{
    protected $signature = 'themes:import
                            {file : Path to CSV file}
                            {--dry-run : Preview import without saving}
                            {--force : Overwrite existing themes}';

    protected $description = 'Import themes from CSV file';

    protected array $fixedThemes = [
        'Sinterklaas',
        'Eerste Kerstdag',
        'Valentijn',
        'Nieuwjaarsdag',
        'Nationale feestdag',
        'Vlaamse feestdag',
        'Halloween',
        'Allerheiligen',
        'Wapenstilstand',
        'Dierendag',
        'Driekoningen',
        'Lichtmis',
        'French Fries Day',
        'Complimentendag',
        '1 april',
        'Dag van de ouderen',
        'Coming Out Day',
        'Dag van de witte stok',
        'Dag van de tolerantie',
        'Dag van de mensenrechten',
        'Dag van de migrant',
        'Dag van mensen met een beperking',
        'Vrijwilligersdag',
        'Dag van de moedertaal',
        'Drink wine day',
        'Dag van het therapiedier',
        'Kinderrechtendag',
        'Wereldmannendag',
        'Wereld Diabetes Dag',
        'Reumadag',
        'Balletdag',
        'Dag van de jeugdbeweging',
        'Dag van de verzorgenden en zorgkundigen',
        'Dag van de ergotherapeut',
        'Dag van de mantelzorger',
        'Wereldyogadag',
        'Plant-een-bloem dag',
        'Lente',
        'Zomer',
        'Herfst',
        'Winter',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return 1;
        }

        $this->info($dryRun ? 'DRY RUN - No changes will be saved' : 'Importing themes...');

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $imported = 0;
        $skipped = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            if (empty($data['title'])) {
                continue;
            }

            $dateType = $this->determineType($data['title']);
            $startDate = $this->normalizeDate($data['start'] ?? null);
            $endDate = $this->normalizeDate($data['end'] ?? null, $startDate);

            $themeData = [
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'start' => $startDate,
                'end' => $endDate,
                'is_month' => ($data['is_month'] ?? '') === '1',
                'date_type' => $dateType,
                'airtable_id' => $data['airtable_id'] ?? null,
            ];

            $existing = null;
            if (! empty($data['airtable_id'])) {
                $existing = Theme::where('airtable_id', $data['airtable_id'])->first();
            }

            if ($existing) {
                if ($force) {
                    if (! $dryRun) {
                        $existing->update($themeData);
                    }
                    $updated++;
                    $this->line("  Updated: {$themeData['title']} [{$dateType}]");
                } else {
                    $skipped++;
                    $this->line("  Skipped (exists): {$themeData['title']}");
                }
            } else {
                if (! $dryRun) {
                    Theme::create($themeData);
                }
                $imported++;
                $this->line("  Imported: {$themeData['title']} [{$dateType}] - {$startDate?->format('d/m/Y')}");
            }
        }

        fclose($handle);

        $this->newLine();
        $this->info('Results:');
        $this->line("  Imported: {$imported}");
        $this->line("  Updated: {$updated}");
        $this->line("  Skipped: {$skipped}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to save changes.');
        }

        return 0;
    }

    protected function determineType(string $title): string
    {
        $title = trim($title);

        foreach ($this->fixedThemes as $fixedTheme) {
            if (strcasecmp($title, $fixedTheme) === 0) {
                return 'fixed';
            }
        }

        return 'variable';
    }

    protected function normalizeDate(?string $dateStr, ?Carbon $referenceDate = null): ?Carbon
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }

        $now = now();
        $currentYear = $now->year;

        $normalizedDate = $date->copy()->year($currentYear);

        if ($normalizedDate->isPast() && $normalizedDate->diffInDays($now) > 7) {
            $normalizedDate->addYear();
        }

        return $normalizedDate;
    }
}
