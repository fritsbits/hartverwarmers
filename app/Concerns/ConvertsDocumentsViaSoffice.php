<?php

namespace App\Concerns;

trait ConvertsDocumentsViaSoffice
{
    private function convertToPdf(string $sourcePath): ?string
    {
        $outputDir = sys_get_temp_dir();
        $basename = pathinfo($sourcePath, PATHINFO_FILENAME);

        $command = sprintf(
            'soffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($outputDir),
            escapeshellarg($sourcePath)
        );

        $this->info('  Converting to PDF via LibreOffice...');
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('  LibreOffice conversion failed: '.implode("\n", $output));

            return null;
        }

        $pdfPath = $outputDir.'/'.$basename.'.pdf';

        if (! file_exists($pdfPath)) {
            $this->error('  PDF output not found at: '.$pdfPath);

            return null;
        }

        $this->info('  PDF created successfully.');

        return $pdfPath;
    }
}
