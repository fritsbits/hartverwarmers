<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiche_id',
        'source_file_id',
        'original_filename',
        'path',
        'mime_type',
        'size_bytes',
        'sort_order',
        'preview_images',
        'total_slides',
        'extracted_text',
    ];

    protected function casts(): array
    {
        return [
            'preview_images' => 'array',
        ];
    }

    public function hasPreviewImages(): bool
    {
        return ! empty($this->preview_images);
    }

    /**
     * @return array<int, string>
     */
    public function thumbnailPaths(): array
    {
        if (! $this->hasPreviewImages()) {
            return [];
        }

        return array_map(
            fn (string $path) => Str::replaceLast('.jpg', '-thumb.jpg', $path),
            $this->preview_images,
        );
    }

    public function hasExtractedText(): bool
    {
        return ! empty($this->extracted_text);
    }

    public function typeLabel(): string
    {
        return match (true) {
            str_contains($this->mime_type, 'presentation'),
            str_contains($this->mime_type, 'powerpoint') => 'PowerPoint',
            str_contains($this->mime_type, 'pdf') => 'PDF',
            str_contains($this->mime_type, 'image') => 'Afbeelding',
            str_contains($this->mime_type, 'word'),
            str_contains($this->mime_type, 'document') => 'Word-document',
            str_contains($this->mime_type, 'spreadsheet'),
            str_contains($this->mime_type, 'excel') => 'Excel',
            default => 'Bestand',
        };
    }

    public function pageUnitLabel(): string
    {
        return 'slides';
    }

    public function formattedSize(): string
    {
        $mb = $this->size_bytes / (1024 * 1024);

        return $mb >= 1
            ? number_format($mb, 0).' MB'
            : number_format($this->size_bytes / 1024, 0).' KB';
    }

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(Fiche::class);
    }

    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_file_id');
    }

    public function pdfVersion(): HasOne
    {
        return $this->hasOne(self::class, 'source_file_id');
    }

    public function isGenerated(): bool
    {
        return $this->source_file_id !== null;
    }

    public function isConvertibleToPdf(): bool
    {
        return str_contains($this->mime_type, 'presentation')
            || str_contains($this->mime_type, 'powerpoint')
            || str_contains($this->mime_type, 'word')
            || str_contains($this->mime_type, 'document');
    }
}
