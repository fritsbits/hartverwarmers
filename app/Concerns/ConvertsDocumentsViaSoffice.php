<?php

namespace App\Concerns;

use App\Services\PdfConverter;

trait ConvertsDocumentsViaSoffice
{
    private function convertToPdf(string $sourcePath, string $outputDir): ?string
    {
        $this->info('  Converting to PDF via LibreOffice...');

        $pdfPath = app(PdfConverter::class)->convert($sourcePath, $outputDir);

        if (! $pdfPath) {
            $this->error('  LibreOffice conversion failed.');

            return null;
        }

        $this->info('  PDF created successfully.');

        return $pdfPath;
    }
}
