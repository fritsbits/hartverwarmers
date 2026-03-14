<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'ip_address',
        'file_hash',
        'original_filename',
        'disclaimer_accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'disclaimer_accepted_at' => 'datetime',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
