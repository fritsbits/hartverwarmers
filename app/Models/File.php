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
    ];

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(Fiche::class);
    }
}
