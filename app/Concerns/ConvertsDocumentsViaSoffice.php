<?php

namespace App\Concerns;

use App\Services\PdfConverter;

trait ConvertsDocumentsViaSoffice
{
    private function convertToPdf(string $sourcePath): ?string
    {
        $this->info('  Converting to PDF via LibreOffice...');

        $converter = app(PdfConverter::class);
        $pdfPath = $converter->convert($sourcePath);

        if (! $pdfPath) {
            $this->error('  LibreOffice conversion failed.');

            return null;
        }

        $this->info('  PDF created successfully.');

        return $pdfPath;
    }
}
