<?php

namespace App\Jobs;

use App\Models\File;
use App\Services\FicheAiService;
use App\Services\FileTextExtractor;
use App\Services\PdfConverter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessFicheUploads implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int>  $fileIds
     */
    public function __construct(
        public array $fileIds,
        public ?int $previewFileId,
        public string $cacheKey,
        public string $title,
        public string $description,
    ) {}

    public function handle(): void
    {
        try {
            $this->updateStatus('extracting');
            $this->extractText();
            $this->generatePdfVersions();

            if ($this->previewFileId) {
                GenerateFilePreview::dispatch($this->previewFileId);
            }

            $this->updateStatus('analyzing');
            $results = $this->runAiAnalysis();

            $this->updateStatus('done', $results);
        } catch (\Throwable $e) {
            Log::warning('ProcessFicheUploads failed', ['error' => $e->getMessage()]);
            $this->updateStatus('failed', ['error' => $e->getMessage()]);
        }
    }

    private function extractText(): void
    {
        $extractor = app(FileTextExtractor::class);

        foreach ($this->fileIds as $fileId) {
            $file = File::find($fileId);

            if (! $file || $file->extracted_text !== null) {
                continue;
            }

            $storagePath = Storage::disk('public')->path($file->path);
            $text = $extractor->extract($storagePath, $file->mime_type);

            if ($text) {
                $file->update(['extracted_text' => $text]);
            }
        }
    }

    private function generatePdfVersions(): void
    {
        $converter = app(PdfConverter::class);

        foreach ($this->fileIds as $fileId) {
            $file = File::find($fileId);

            if ($file && $file->isConvertibleToPdf() && ! $file->pdfVersion) {
                $converter->convertAndStore($file);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function runAiAnalysis(): array
    {
        $aiService = app(FicheAiService::class);

        if (! $aiService->isAvailable()) {
            return ['analysis' => null, 'reason' => 'ai_unavailable'];
        }

        $texts = File::whereIn('id', $this->fileIds)
            ->whereNotNull('extracted_text')
            ->pluck('extracted_text')
            ->toArray();

        if (empty($texts)) {
            return ['analysis' => null, 'reason' => 'no_text_extracted'];
        }

        $pipelineStart = microtime(true);

        $analysis = $aiService->analyzeFiles($texts, $this->title, $this->description);

        $pipelineElapsed = round(microtime(true) - $pipelineStart, 2);

        $this->logPipelineMetrics($analysis, $pipelineElapsed, $texts);

        return [
            'analysis' => $analysis,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $analysis
     * @param  array<string>  $texts
     */
    private function logPipelineMetrics(?array $analysis, float $pipelineElapsed, array $texts): void
    {
        $calls = [];
        $totalInputTokens = 0;
        $totalOutputTokens = 0;
        $totalCost = 0.0;

        if ($analysis && ! empty($analysis['_meta'])) {
            $meta = $analysis['_meta'];
            $calls[] = [
                'agent' => $meta['agent'],
                'model' => $meta['model'],
                'input_tokens' => $meta['input_tokens'],
                'output_tokens' => $meta['output_tokens'],
                'elapsed_seconds' => $meta['elapsed_seconds'],
                'estimated_cost' => $meta['estimated_cost'],
            ];
            $totalInputTokens += $meta['input_tokens'];
            $totalOutputTokens += $meta['output_tokens'];
            $totalCost += $meta['estimated_cost'];
        }

        Log::info('FicheAiPipeline completed', [
            'calls' => $calls,
            'totals' => [
                'input_tokens' => $totalInputTokens,
                'output_tokens' => $totalOutputTokens,
                'estimated_cost' => round($totalCost, 4),
                'elapsed_seconds' => $pipelineElapsed,
            ],
            'context' => [
                'file_count' => count($this->fileIds),
                'text_chars' => array_sum(array_map('mb_strlen', $texts)),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function updateStatus(string $step, array $extra = []): void
    {
        Cache::put(
            "fiche-processing:{$this->cacheKey}",
            array_merge(['step' => $step, 'updated_at' => now()->timestamp], $extra),
            3600
        );
    }
}
