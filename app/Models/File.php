<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiche_id',
        'original_filename',
        'path',
        'mime_type',
        'size_bytes',
        'sort_order',
        'preview_images',
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

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(Fiche::class);
    }
}
