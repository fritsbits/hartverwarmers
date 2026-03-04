<?php

namespace App\Services;

use ZipArchive;

class FileTextExtractor
{
    /** @var array<string, string> */
    private const NAMESPACE_URIS = [
        'a' => 'http://schemas.openxmlformats.org/drawingml/2006/main',
        'w' => 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
    ];

    public function extract(string $path, string $mimeType): ?string
    {
        if (! file_exists($path)) {
            return null;
        }

        $isPdf = str_contains($mimeType, 'pdf');
        $isPptx = str_contains($mimeType, 'presentation') || str_contains($mimeType, 'powerpoint');
        $isDocx = str_contains($mimeType, 'word') || str_contains($mimeType, 'document');

        if (! $isPdf && ! $isPptx && ! $isDocx) {
            return null;
        }

        $text = match (true) {
            $isPdf => $this->extractFromPdf($path),
            $isPptx => $this->extractFromPptx($path),
            $isDocx => $this->extractFromDocx($path),
        };

        if ($text === null) {
            return null;
        }

        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    private function extractFromPdf(string $path): ?string
    {
        $command = sprintf('pdftotext %s -', escapeshellarg($path));
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        return implode("\n", $output);
    }

    private function extractFromPptx(string $path): ?string
    {
        return $this->extractFromOfficeXml($path, 'ppt/slides/slide*.xml', 'a', 't');
    }

    private function extractFromDocx(string $path): ?string
    {
        return $this->extractFromOfficeXml($path, 'word/document.xml', 'w', 't');
    }

    private function extractFromOfficeXml(string $path, string $entryPattern, string $nsPrefix, string $tagName): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return null;
        }

        $texts = [];
        $isSlidePattern = str_contains($entryPattern, '*');

        if ($isSlidePattern) {
            $slideEntries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#ppt/slides/slide(\d+)\.xml#', $name, $matches)) {
                    $slideEntries[(int) $matches[1]] = $name;
                }
            }
            ksort($slideEntries);

            $slideNum = 1;
            foreach ($slideEntries as $entryName) {
                $xml = $zip->getFromName($entryName);
                if ($xml === false) {
                    continue;
                }

                $slideText = $this->extractTextFromXml($xml, $nsPrefix, $tagName);
                if ($slideText !== '') {
                    $texts[] = "--- Slide {$slideNum} ---";
                    $texts[] = $slideText;
                }
                $slideNum++;
            }
        } else {
            $xml = $zip->getFromName($entryPattern);
            if ($xml === false) {
                $zip->close();

                return null;
            }

            $texts[] = $this->extractTextFromXml($xml, $nsPrefix, $tagName);
        }

        $zip->close();

        return implode("\n", $texts);
    }

    private function extractTextFromXml(string $xml, string $nsPrefix, string $tagName): string
    {
        $doc = new \DOMDocument;
        $doc->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace($nsPrefix, self::NAMESPACE_URIS[$nsPrefix]);

        $nodes = $xpath->query("//{$nsPrefix}:{$tagName}");
        $parts = [];

        foreach ($nodes as $node) {
            $text = trim($node->textContent);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode(' ', $parts);
    }
}
